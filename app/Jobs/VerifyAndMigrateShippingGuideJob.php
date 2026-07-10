<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\comercial\dynamics\SaleShippingGuideDynamicsService;
use App\Http\Services\ap\comercial\dynamics\ShippingGuideMigrationLogService;
use App\Http\Services\ap\comercial\dynamics\TransferShippingGuideDynamicsService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Migra guías de remisión del área COMERCIAL hacia Microsoft Dynamics 365
 * escribiendo en la base de datos intermedia (dbtp) y verificando su procesamiento.
 *
 * FLUJO GENERAL:
 *   1. Toma guías con migration_status = pending/in_progress/failed del área COMERCIAL.
 *   2. Según el motivo de traslado determina el tipo:
 *      - VENTA   → escribe en neInTbTransaccionInventario (cabecera, detalle, serial/VIN).
 *      - TRANSFER → escribe en neInTbTransferenciaInventario (cabecera, detalle, serial/VIN).
 *   3. Para cada uno de los 3 pasos verifica si el registro YA existe en la BD intermedia:
 *      - Si NO existe → lo sincroniza (inserta) y marca ProcesoEstado = 0 (en espera).
 *      - Si existe    → lee ProcesoEstado devuelto por Dynamics:
 *          * 0 = aún procesando   → sin cambios.
 *          * 1 = aceptado         → marca el step como completado; en transferencias
 *                                   también actualiza el warehouse_id del vehículo.
 *   4. Cuando los 3 pasos están en ProcesoEstado = 1, marca la guía como
 *      migration_status = completed y status_dynamic = 1.
 *   5. Las cancelaciones usan steps "_REVERSAL" con asterisco en el TransaccionId/TransferenciaId.
 *
 * PUEDE DESPACHARSE:
 *   - Con un $shippingGuideId específico → procesa solo esa guía.
 *   - Sin ID                             → procesa todas las guías pendientes.
 *
 * COLA: shipping_guides | tries: 2 | timeout: 300 s | backoff: 120 s
 */
class VerifyAndMigrateShippingGuideJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2; // Reducido de 5 → 2 para evitar crecimiento exponencial de jobs
  public int $timeout = 300;
  public int $backoff = 120; // Aumentado a 120 segundos para dar más tiempo entre reintentos

  public function __construct(public ?int $shippingGuideId = null)
  {
    $this->onQueue('shipping_guides');
  }

  /**
   * @throws Exception
   */
  public function handle(
    DatabaseSyncService              $syncService,
    ShippingGuideMigrationLogService $logService
  ): void
  {
    $saleService = new SaleShippingGuideDynamicsService($syncService, $logService);
    $transferService = new TransferShippingGuideDynamicsService($syncService, $logService);

    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId, $logService, $saleService, $transferService);
      } else {
        $this->processAllPendingShippingGuides($logService, $saleService, $transferService);
      }
    } catch (Exception $e) {
      Log::error('Error en VerifyAndMigrateShippingGuideJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error'             => $e->getMessage(),
      ]);

      if ($this->shippingGuideId) {
        $guide = ShippingGuides::find($this->shippingGuideId);
        if ($guide) {
          $logService->checkAndUpdateCompletionStatus($guide);
        }
      }

      throw $e;
    }
  }

  protected function processAllPendingShippingGuides(
    ShippingGuideMigrationLogService     $logService,
    SaleShippingGuideDynamicsService     $saleService,
    TransferShippingGuideDynamicsService $transferService
  ): void
  {
    $pendingGuides = ShippingGuides::whereIn('migration_status', [
      VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
      VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
    ])
      ->where(function ($q) {
        $q->where('aceptada_por_sunat', true)
          ->orWhere('document_type', ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA);
      })
      ->where(function ($q) {
        // Guías de compra (transfer_reason_id = 15) solo migran después de ser recepcionadas
        $q->where(function ($inner) {
          $inner->where('transfer_reason_id', \App\Models\gp\maestroGeneral\SunatConcepts::TRANSFER_REASON_COMPRA)
            ->where('is_received', true);
        })->orWhere('transfer_reason_id', '!=', \App\Models\gp\maestroGeneral\SunatConcepts::TRANSFER_REASON_COMPRA)
          ->orWhereNull('transfer_reason_id');
      })
      ->where('area_id', ApMasters::AREA_COMERCIAL)
      ->whereNull('deleted_at')
      ->get();

    foreach ($pendingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id, $logService, $saleService, $transferService);
      } catch (Exception $e) {
        Log::error('Error procesando guía de remisión', [
          'shipping_guide_id' => $guide->id,
          'error'             => $e->getMessage(),
        ]);
        continue;
      }
    }
  }

  protected function processShippingGuide(
    int                                  $shippingGuideId,
    ShippingGuideMigrationLogService     $logService,
    SaleShippingGuideDynamicsService     $saleService,
    TransferShippingGuideDynamicsService $transferService
  ): void
  {
    $shippingGuide = ShippingGuides::with([
      'vehicleMovement.vehicle.model',
      'sedeTransmitter',
      'sedeReceiver',
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      return;
    }

    if ($shippingGuide->area_id !== ApMasters::AREA_COMERCIAL) {
      Log::warning('Guía de remisión no es del área COMERCIAL, se omite la migración', [
        'shipping_guide_id' => $shippingGuide->id,
        'area_id'           => $shippingGuide->area_id,
        'expected_area_id'  => ApMasters::AREA_COMERCIAL,
      ]);
      return;
    }

    if (!$shippingGuide->vehicle_movement_id) {
      Log::error('Guía de remisión del área COMERCIAL sin vehicle_movement_id', [
        'shipping_guide_id' => $shippingGuide->id,
        'area_id'           => $shippingGuide->area_id,
      ]);
      throw new Exception("La guía de remisión del área COMERCIAL debe tener un vehicle_movement_id válido. ShippingGuide ID: {$shippingGuide->id}");
    }

    // Guías de compra solo migran después de ser recepcionadas
    if (
      $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_COMPRA
      && !$shippingGuide->is_received
    ) {
      Log::info('Guía de compra aún no recepcionada, se omite la migración', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reason_id' => $shippingGuide->transfer_reason_id,
        'is_received'        => $shippingGuide->is_received,
      ]);
      return;
    }

    if ($shippingGuide->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      $shippingGuide->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);
    }

    $isSale = $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA;

    if ($isSale) {
      $logService->ensureSaleLogsExist($shippingGuide);
      $saleService->verifyTransaction($shippingGuide);
      $saleService->verifyTransactionDetail($shippingGuide);
      $saleService->verifyTransactionSerial($shippingGuide);
    } else {
      $logService->ensureTransferLogsExist($shippingGuide);
      $transferService->verifyTransfer($shippingGuide);
      $transferService->verifyTransferDetail($shippingGuide);
      $transferService->verifyTransferSerial($shippingGuide);
    }

    $logService->checkAndUpdateCompletionStatus($shippingGuide);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateShippingGuideJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error'             => $exception->getMessage(),
    ]);

    if ($this->shippingGuideId) {
      ShippingGuides::where('id', $this->shippingGuideId)
        ->update(['migration_status' => 'failed']);
    }
  }
}
