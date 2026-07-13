<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Http\Services\ap\postventa\gestionProductos\TransferReceptionService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consulta Dynamics para confirmar que una guía ya fue contabilizada ("CONTABILIZADO")
 * y desencadena los efectos posteriores en el sistema local.
 *
 * Este job se ejecuta DESPUÉS de que VerifyAndMigrateShippingGuideJob completó la migración
 * (migration_status = completed). Su responsabilidad es leer el estado real en Dynamics
 * a través de Stored Procedures y actuar en consecuencia.
 *
 * TRES FLUJOS SEGÚN EL TIPO DE GUÍA:
 *
 *   A) Guía COMERCIAL de VENTA (area_id = COMERCIAL, transfer_reason_id = VENTA):
 *      - Consulta SP: EXEC neIvConsultarAjustesInventario
 *      - Si el Numero del resultado coincide con dyn_series → movimiento existente en Dynamics.
 *      - Efectos: marca is_accounted = true, cierra ApVehicleDelivery (status = 'completed')
 *        con fecha de entrega real, y avanza la Oportunidad a estado DELIVERED.
 *
 *   B) Guía COMERCIAL de TRANSFERENCIA (area_id = COMERCIAL, no VENTA; incluye GUIA_INTERNA):
 *      - Consulta SP: EXEC neIvConsultarTransferenciasInventario '{transactionId}'
 *      - Verifica si Estado = 'CONTABILIZADO'.
 *      - Solo actualiza is_accounted / is_annulled. No genera movimientos de inventario
 *        (eso es exclusivo del área postventa).
 *
 *   C) Guía de TRANSFERENCIA (postventa):
 *      - Consulta SP: EXEC neIvConsultarTransferenciasInventario '{transactionId}'
 *      - Verifica si Estado = 'CONTABILIZADO'.
 *      - Guía activa   → marca is_accounted = true y genera movimiento de ENTRADA de inventario
 *                        en el sistema local (TransferReceptionService::generateInventoryMovement).
 *      - Guía cancelada → marca is_annulled = true y genera movimiento inverso de devolución
 *                         (TransferReceptionService::generateReversalInventoryMovement).
 *
 * PUEDE DESPACHARSE:
 *   - Con un $shippingGuideId específico → procesa solo esa guía.
 *   - Sin ID                             → procesa todas las guías con migration_status = completed
 *                                          que aún no están contabilizadas o anuladas.
 *
 * COLA: shipping_guide_sync | tries: 3 | timeout: 300 s
 */
class SyncShippingGuideDynamicsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  /**
   * @param int|null $shippingGuideId ID de la guía a procesar; null = procesa todas las pendientes.
   */
  public function __construct(
    public ?int $shippingGuideId = null
  )
  {
    $this->onQueue('shipping_guide_sync');
  }

  /**
   * Punto de entrada del job.
   * Delega a processShippingGuide() si hay ID, o a processAllShippingGuides() en modo batch.
   */
  public function handle(): void
  {
    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId);
      } else {
        $this->processAllShippingGuides();
      }
    } catch (\Exception $e) {
      Log::error('Error en SyncShippingGuideDynamicsJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Carga en batch todas las guías que aún requieren confirmación contable:
   *   - Guías activas con migration_status = completed e is_accounted = false.
   *   - Guías canceladas con migration_status = completed e is_annulled = false.
   * Incluye guías internas (GUIA_INTERNA) que no pasan por SUNAT.
   * Procesa cada una llamando a processShippingGuide(); los errores individuales
   * se loguean y no detienen el resto del lote.
   */
  protected function processAllShippingGuides(): void
  {
    $shippingGuides = ShippingGuides::whereNotNull('document_number')
      ->where(function ($q) {
        $q->where('aceptada_por_sunat', true)
          ->orWhere('document_type', ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA);
      })
      ->where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where(function ($q) {
        $q->where(function ($q2) {
          $q2->where('status', true)
            ->where('is_accounted', false);
        })
          ->orWhere(function ($q2) {
            $q2->where('status', false)
              ->where('is_annulled', false);
          })
          // Guías ya contabilizadas pero cuya issue_date aún no llegó cuando se procesaron:
          // vuelven al batch hasta que la fecha se alcance y el movimiento pueda crearse.
          ->orWhere(function ($q2) {
            $q2->where('status', true)
              ->where('is_accounted', true)
              ->where('area_id', ApMasters::AREA_COMERCIAL)
              ->whereNotIn('transfer_reason_id', [SunatConcepts::TRANSFER_REASON_VENTA])
              ->where('issue_date', '>', now()->startOfDay());
          });
      })
      ->get();

    if ($shippingGuides->isEmpty()) {
      return;
    }

    foreach ($shippingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id);
      } catch (\Exception $e) {
        Log::error('Error procesando guía en lote', [
          'shipping_guide_id' => $guide->id,
          'error' => $e->getMessage()
        ]);
        continue;
      }
    }
  }

  /**
   * Orquesta el procesamiento de una guía individual.
   * Bifurca según área y motivo de traslado — nunca por el prefijo del dyn_series:
   *   - COMERCIAL + VENTA         → processCommercialDeliveryGuide()
   *   - COMERCIAL + otros/GTI     → processCommercialTransferGuide()
   *   - Otra área (postventa)     → flujo de transferencia con movimientos de inventario
   */
  protected function processShippingGuide(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::find($shippingGuideId);

    if (!$shippingGuide || !$shippingGuide->document_number) {
      return;
    }

    try {
      if ($shippingGuide->area_id === ApMasters::AREA_COMERCIAL) {
        if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA) {
          $this->processCommercialDeliveryGuide($shippingGuide);
        } else {
          $this->processCommercialTransferGuide($shippingGuide);
        }
        return;
      }

      // Área POSTVENTA → neIvConsultarTransferenciasInventario + movimientos de inventario
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);
      $result = $this->consultStoredProcedure($transactionId);

      if (!$result) {
        Log::warning('No se encontró resultado en Dynamics para la guía', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId,
        ]);
        return;
      }

      $dynSeriesFromDynamics = isset($result->Documento) ? trim($result->Documento) : null;

      if (empty($dynSeriesFromDynamics)) {
        Log::warning('El resultado de Dynamics no contiene Serie', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId
        ]);
        return;
      }

      $isAccounted = ($result->Estado === 'CONTABILIZADO');

      // Actualizar el campo correspondiente según si está cancelada o no
      if ($isCancelled) {
        $shippingGuide->update([
          'is_annulled' => $isAccounted,
        ]);

        if (!$isAccounted) {
          Log::info('La reversión aún no está contabilizada en Dynamics', [
            'shipping_guide_id' => $shippingGuide->id,
            'transaction_id' => $transactionId
          ]);
          return;
        }
      } else {
        $shippingGuide->update([
          'is_accounted' => $isAccounted,
        ]);

        if (!$isAccounted) {
          Log::info('La transferencia aún no está contabilizada en Dynamics', [
            'shipping_guide_id' => $shippingGuide->id,
            'transaction_id' => $transactionId
          ]);
          return;
        }
      }

      $transferReceptionService = app(TransferReceptionService::class);

      // Traslado de sede: no hay recepción; el movimiento de inventario se genera
      // directamente desde el TRANSFER_OUT una vez que Dynamics confirma la contabilización.
      if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        if (!$isCancelled && $this->isIssueDateReached($shippingGuide)) {
          $transferOutMovement = InventoryMovement::where('reference_type', ShippingGuides::class)
            ->where('reference_id', $shippingGuide->id)
            ->where('movement_type', InventoryMovement::TYPE_TRANSFER_OUT)
            ->first();

          if ($transferOutMovement && $transferOutMovement->status !== InventoryMovement::STATUS_APPROVED) {
            $transferReceptionService->generateInventoryMovementFromTransferOut($transferOutMovement);
          }
        }
        return;
      }

      // Verificar si la guía ya fue procesada (para evitar duplicados)
      $transferReception = TransferReception::where('shipping_guide_id', $shippingGuide->id)->first();

      if (!$transferReception) {
        Log::warning('No se encontró la recepción de transferencia asociada', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId
        ]);
        return;
      }

      $transferOutMovement = $transferReception->transferMovement;

      if (!$transferOutMovement) {
        Log::warning('No se encontró el movimiento de transferencia asociado', [
          'shipping_guide_id' => $shippingGuide->id,
          'transfer_reception_id' => $transferReception->id
        ]);
        return;
      }

      if (!$this->isIssueDateReached($shippingGuide)) {
        return;
      }

      if ($isCancelled) {
        // Para cancelaciones, buscar el movimiento de cancelación (TRANSFER_OUT con almacenes invertidos)
        // El movimiento de cancelación tiene cancelled_inventory_movement_id apuntando al original
        $cancellationMovement = InventoryMovement::where('reference_type', ShippingGuides::class)
          ->where('reference_id', $shippingGuide->id)
          ->where('movement_type', InventoryMovement::TYPE_TRANSFER_OUT)
          ->whereNotNull('cancelled_inventory_movement_id')
          ->first();

        if (!$cancellationMovement) {
          Log::warning('No se encontró el movimiento de cancelación (TRANSFER_OUT invertido)', [
            'shipping_guide_id' => $shippingGuide->id,
            'transfer_reception_id' => $transferReception->id
          ]);
          return;
        }

        // Generar movimiento inverso (devolución) usando el movimiento de CANCELACIÓN
        $transferReceptionService->generateReversalInventoryMovement($transferReception, $cancellationMovement, $shippingGuide);
      } else {
        // Para transferencias normales, generar movimiento de entrada
        $transferReceptionService->generateInventoryMovement($transferReception, $transferOutMovement);
      }
    } catch (\Exception $e) {
      Log::error('Error procesando guía de remisión en Dynamics', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Flujo VENTA comercial (dyn_series con prefijo "CV-").
   * Consulta neIvConsultarAjustesInventario y busca la fila cuyo Numero coincide con dyn_series.
   * Si se encuentra:
   *   1. Marca la guía como is_accounted = true y despacha SyncAccountingEntryJob.
   *   2. Avanza ApVehicleDelivery a status_delivery='delivered' y registra real_delivery_date.
   *   3. Avanza la Oportunidad al estado DELIVERED.
   */
  protected function processCommercialDeliveryGuide(ShippingGuides $shippingGuide): void
  {
    $results = $this->consultAjustesInventario($shippingGuide->dyn_series);

    $found = collect($results)->first(
      fn($row) => trim($row->Numero ?? '') === $shippingGuide->dyn_series
    );

    if (!$found) {
      return;
    }

    $wasAlreadyAccounted = $shippingGuide->is_accounted;
    $shippingGuide->update(['is_accounted' => true]);

    if (!$wasAlreadyAccounted) {
      SyncAccountingEntryJob::dispatch($shippingGuide->id);
    }

    if (!$this->isIssueDateReached($shippingGuide)) {
      return;
    }

    ApVehicleDelivery::where('shipping_guide_id', $shippingGuide->id)
      ->update([
        'status_delivery' => 'delivered',
        'real_delivery_date' => now(),
      ]);

    $delivery = ApVehicleDelivery::where('shipping_guide_id', $shippingGuide->id)->first();
    if ($delivery?->vehicle_id) {
      $quote = PurchaseRequestQuote::where('ap_vehicle_id', $delivery->vehicle_id)->first();
      if ($quote?->opportunity_id) {
        Opportunity::where('id', $quote->opportunity_id)
          ->update(['opportunity_status_id' => Opportunity::DELIVERED_ID]);
      }
    }
  }

  /**
   * Flujo TRANSFERENCIA comercial (area COMERCIAL, motivo distinto a VENTA; incluye GUIA_INTERNA).
   * Consulta neIvConsultarTransferenciasInventario y verifica Estado = 'CONTABILIZADO'.
   * Solo actualiza is_accounted o is_annulled; no genera movimientos de inventario
   * porque eso es responsabilidad exclusiva del área postventa.
   * @throws \Throwable
   */
  protected function processCommercialTransferGuide(ShippingGuides $shippingGuide): void
  {
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
    $transactionId = $shippingGuide->getDynamicsTransferTransactionId($isCancelled);
    $result = $this->consultStoredProcedure($transactionId);

    if (!$result) {
      Log::warning('No se encontró resultado en Dynamics para la guía comercial', [
        'shipping_guide_id' => $shippingGuide->id,
        'transaction_id' => $transactionId,
      ]);
      return;
    }

    $isAccounted = ($result->Estado === 'CONTABILIZADO');

    if ($isCancelled) {
      $shippingGuide->update(['is_annulled' => $isAccounted]);
      if (!$isAccounted) {
        Log::info('La reversión comercial aún no está contabilizada en Dynamics', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId,
        ]);
      }
    } else {
      $wasAlreadyAccounted = $shippingGuide->is_accounted;
      $shippingGuide->update(['is_accounted' => $isAccounted]);
      if (!$isAccounted) {
        Log::info('La transferencia comercial aún no está contabilizada en Dynamics', [
          'shipping_guide_id' => $shippingGuide->id,
          'transaction_id' => $transactionId,
        ]);
        return;
      }

      // Cuando llega issue_date → mover vehículo a ALM destino con estado INVENTARIO_VN
      if ($this->isIssueDateReached($shippingGuide)) {
        $this->createCommercialTransferVehicleMovement($shippingGuide, $wasAlreadyAccounted);
      }
    }
  }

  /**
   * Crea el movimiento de vehículo para una guía comercial de transferencia,
   * evitando duplicados: si la guía ya estaba contabilizada ($wasAlreadyAccounted=true)
   * se verifica que no exista ya un movimiento con la misma observación antes de crear uno.
   */
  private function createCommercialTransferVehicleMovement(ShippingGuides $shippingGuide, bool $wasAlreadyAccounted): void
  {
    $vehicle = $shippingGuide->vehicleMovement?->vehicle;
    if (!$vehicle) {
      return;
    }

    // Si ya estaba contabilizada antes de este run (caso: issue_date futura que ahora llegó),
    // verificar que no exista ya un movimiento creado para evitar duplicados.
    if ($wasAlreadyAccounted) {
      $alreadyExists = $vehicle->vehicleMovements()
        ->where('movement_type', VehicleMovement::INTERNAL_TRANSFER)
        ->where('observation', 'like', "%{$shippingGuide->document_number}%")
        ->exists();
      if ($alreadyExists) {
        return;
      }
    }

    if ($shippingGuide->document_type === ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA) {
      (new VehicleMovementService())->storeInternalTransferCompletedVehicleMovement($vehicle, $shippingGuide);
    } elseif (
      $shippingGuide->document_type === ShippingGuides::DOCUMENT_TYPE_GR
      && $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE
    ) {
      (new VehicleMovementService())->storeInterCompanyTransferCompletedVehicleMovement($vehicle, $shippingGuide);
    }
  }

  private function isIssueDateReached(ShippingGuides $shippingGuide): bool
  {
    return !$shippingGuide->issue_date || now()->startOfDay()->gte($shippingGuide->issue_date->startOfDay());
  }

  /**
   * Ejecuta EXEC neIvConsultarTransferenciasInventario '{transactionId}' en dbtest.
   * Retorna el primer resultado (objeto con campos Documento, Estado, etc.)
   * o null si el SP no devuelve filas para ese ID de transacción.
   */
  protected function consultStoredProcedure(string $transactionId): ?object
  {
    try {
      // Ejecutar el PA: EXEC neIvConsultarTransferenciasInventario 'PTRA-00000157'
      $results = DB::connection(Company::CONNECTION_DYNAMICS_3)
        ->select("EXEC neIvConsultarTransferenciasInventario '{$transactionId}'");

      // El PA debería retornar un resultado con el campo Serie (dyn_series)
      if (!empty($results) && isset($results[0])) {
        return $results[0];
      }

      return null;
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarTransferenciasInventario', [
        'transaction_id' => $transactionId,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Ejecuta EXEC neIvConsultarAjustesInventario en dbtest y retorna todas las filas.
   * El filtrado por Numero se hace en el caller (processCommercialDeliveryGuide).
   *
   * TODO: pasar $documentNumber como parámetro al SP cuando Dynamics lo soporte.
   */
  protected function consultAjustesInventario(string $documentNumber): array
  {
    try {
      return DB::connection(Company::CONNECTION_DYNAMICS_3)
        ->select("EXEC neIvConsultarAjustesInventario");
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario', [
        'document_number' => $documentNumber,
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Callback de Laravel cuando el job agota todos los reintentos.
   * Deja registro de error con traza completa para diagnóstico manual.
   */
  public function failed(\Throwable $exception): void
  {
    Log::error('SyncShippingGuideDynamicsJob falló definitivamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);
  }
}
