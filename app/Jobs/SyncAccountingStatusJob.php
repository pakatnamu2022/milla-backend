<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationsService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * php artisan queue:work --tries=3
 */
class SyncAccountingStatusJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  public function __construct()
  {
    $this->onQueue('electronic_documents');
  }

  public function handle(): void
  {
    $documents = ElectronicDocument::where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where(function ($query) {
        $query->where('is_accounted', false)
          ->orWhereNull('is_accounted');
      })
      ->get();

    foreach ($documents as $document) {
      try {
        $sopRecord = DB::connection('dbtest')
          ->table('SOP30200')
          ->where('SOPNUMBE', 'like', '%' . $document->full_number . '%')
          ->first();

        if ($sopRecord) {
          $isAnnulled = $sopRecord->VOIDSTTS == "1";

          if (!$isAnnulled) {
            $rmRecord = DB::connection('dbtest')
              ->table('RM20101')
              ->where('DOCNUMBR', 'like', '%' . $document->full_number . '%')
              ->whereNot('RMDTYPAL', '9')
              ->first();

            if ($rmRecord) {
              $isAnnulled = $rmRecord->VOIDSTTS == "1";
            }
          }

          $wasAccounted = $document->is_accounted;

          $document->update([
            'is_accounted' => true,
            'is_annulled' => $isAnnulled,
          ]);

          if (!$wasAccounted && !$isAnnulled) {
            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              $this->createInventoryMovementIfApplicable($document);
            }
          } elseif ($wasAccounted && !$isAnnulled && $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
            // Reversión de estados e inventario para NC contabilizadas
            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              // Comercial ya tiene su lógica (no tocar)
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              // Postventa - Nueva lógica de reversión
              $this->reversePostventaStatusIfApplicable($document);
            }
          }
        } else {
          $document->update([
            'is_accounted' => false,
            'is_annulled' => false,
          ]);
        }
      } catch (Throwable $e) {
        Log::error('Error al sincronizar estado contable desde Dynamics', [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'error' => $e->getMessage(),
        ]);
      }
    }
  }

  private function restoreVehicleToInventoryIfApplicable(ElectronicDocument $document): void
  {
    if ($document->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
      return;
    }

    $restorableTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    if (!in_array($document->sunat_concept_credit_note_type_id, $restorableTypes)) {
      return;
    }

    $originalDocument = $document->originalDocument;

    if (!$originalDocument || !$originalDocument->ap_vehicle_movement_id) {
      return;
    }

    $vehicle = $originalDocument->vehicle;

    if (!$vehicle) {
      return;
    }

    $vehicle->update(['ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN]);

    Log::info('Vehículo devuelto al inventario por NC contabilizada', [
      'credit_note_id' => $document->id,
      'credit_note_number' => $document->full_number,
      'original_document_id' => $originalDocument->id,
      'vehicle_id' => $vehicle->id,
      'vehicle_vin' => $vehicle->vin,
    ]);
  }

  /**
   * Crear movimiento de inventario para cotizaciones u órdenes de trabajo
   * solo después de que la última factura haya sido contabilizada en Dynamics
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function createInventoryMovementIfApplicable(ElectronicDocument $document): void
  {
    // Procesar cotizaciones
    if ($document->order_quotation_id) {
      $this->createInventoryMovementForQuotation($document->order_quotation_id);
    }

    // Procesar órdenes de trabajo
    if ($document->work_order_id) {
      $this->createInventoryMovementForWorkOrder($document->work_order_id);
    }
  }

  /**
   * Crear movimiento de inventario para cotización totalmente pagada
   *
   * Ahora primero evalúa y actualiza el estado de pago usando el método centralizado
   * en ApOrderQuotationsService antes de crear el movimiento de inventario.
   *
   * @param int $quotationId
   * @return void
   */
  private function createInventoryMovementForQuotation(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Evaluar y actualizar el estado de pago de la cotización
      // Este método centralizado actualiza is_fully_paid y status si cumple las condiciones
      $quotationService = app(ApOrderQuotationsService::class);
      $quotationService->evaluateAndUpdateQuotationPaymentStatus($quotationId);

      // Refrescar el modelo para obtener los valores actualizados
      $quotation->refresh();

      // Verificar que la cotización esté totalmente pagada Y no tenga salida de inventario generada
      if (!$quotation->is_fully_paid || $quotation->output_generation_warehouse) {
        return;
      }

      // Crear la salida de inventario
      $inventoryMovementService = app(InventoryMovementService::class);
      $inventoryMovementService->createSaleFromQuotation($quotationId);
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de inventario para cotización', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Crear movimiento de inventario para orden de trabajo totalmente facturada
   *
   * @param int $workOrderId
   * @return void
   */
  private function createInventoryMovementForWorkOrder(int $workOrderId): void
  {
    try {
      $workOrder = ApWorkOrder::find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Verificar que la orden de trabajo esté totalmente facturada Y no tenga salida de inventario generada
      if (!$workOrder->is_invoiced || $workOrder->output_generation_warehouse) {
        return;
      }

      // Crear la salida de inventario
      $inventoryMovementService = app(InventoryMovementService::class);
      $inventoryMovementService->createSaleFromWorkOrder($workOrderId);
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de inventario para orden de trabajo', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir estados e inventario de cotizaciones/OT cuando una NC de postventa es contabilizada
   * Solo aplica para NC de facturas finales (is_advance_payment = 0)
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function reversePostventaStatusIfApplicable(ElectronicDocument $document): void
  {
    // Solo procesar áreas de postventa
    if (!in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
      return;
    }

    // Solo procesar Notas de Crédito
    if ($document->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
      return;
    }

    // Obtener documento original
    $originalDocument = $document->originalDocument;
    if (!$originalDocument) {
      return;
    }

    // Solo revertir si el documento original es FACTURA FINAL (no anticipo)
    if ($originalDocument->is_advance_payment) {
      return; // Los anticipos no generan movimiento de inventario
    }

    // Delegar según tipo de NC
    $creditNoteType = $document->sunat_concept_credit_note_type_id;

    switch ($creditNoteType) {
      case SunatConcepts::ID_CREDIT_NOTE_ANULACION:
        $this->reverseForAnulacion($originalDocument);
        break;

      case SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL:
        $this->reverseForDevolucionTotal($originalDocument);
        break;

      case SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_ITEM:
        // TODO: Pendiente definir lógica de reversión parcial
        Log::info('NC por devolución de ítem contabilizada - Pendiente implementar reversión parcial', [
          'credit_note_id' => $document->id,
          'original_document_id' => $originalDocument->id,
        ]);
        break;

      default:
        // Otros tipos de NC (descuentos, bonificaciones, etc.) no requieren reversión de estados/inventario
        Log::info('NC contabilizada sin reversión de estados', [
          'credit_note_id' => $document->id,
          'credit_note_type_id' => $creditNoteType,
          'original_document_id' => $originalDocument->id,
        ]);
        break;
    }
  }

  /**
   * Reversión para NC por Anulación (código 01)
   * Revierte TODO: estados + inventario
   *
   * @param ElectronicDocument $originalDocument
   * @return void
   */
  private function reverseForAnulacion(ElectronicDocument $originalDocument): void
  {
    // Revertir cotización si existe
    if ($originalDocument->order_quotation_id) {
      $this->reverseQuotationStatus($originalDocument->order_quotation_id);
    }

    // Revertir orden de trabajo si existe
    if ($originalDocument->work_order_id) {
      $this->reverseWorkOrderStatus($originalDocument->work_order_id);
    }
  }

  /**
   * Reversión para NC por Devolución Total (código 06)
   * Revierte TODO: estados + inventario
   *
   * @param ElectronicDocument $originalDocument
   * @return void
   */
  private function reverseForDevolucionTotal(ElectronicDocument $originalDocument): void
  {
    // Misma lógica que anulación
    $this->reverseForAnulacion($originalDocument);
  }

  /**
   * Revertir estados e inventario de una cotización
   *
   * @param int $quotationId
   * @return void
   */
  private function reverseQuotationStatus(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Revertir inventario si existe
      if ($quotation->output_generation_warehouse) {
        $this->reverseInventoryForQuotation($quotation);
      }

      // Revertir estados
      $quotation->update([
        'status' => ApOrderQuotations::STATUS_POR_FACTURAR,
        'is_fully_paid' => false,
        'output_generation_warehouse' => false,
      ]);

      Log::info('Cotización revertida por NC contabilizada', [
        'quotation_id' => $quotation->id,
        'quotation_number' => $quotation->quotation_number,
      ]);
    } catch (Exception $e) {
      Log::error('Error al revertir estado de cotización', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir estados e inventario de una orden de trabajo
   *
   * @param int $workOrderId
   * @return void
   */
  private function reverseWorkOrderStatus(int $workOrderId): void
  {
    try {
      $workOrder = ApWorkOrder::find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Revertir inventario si existe
      if ($workOrder->output_generation_warehouse) {
        $this->reverseInventoryForWorkOrder($workOrder);
      }

      // Revertir estados
      $workOrder->update([
        'status_id' => ApMasters::FINISHED_WORK_ORDER_ID,
        'is_invoiced' => false,
        'output_generation_warehouse' => false,
      ]);

      Log::info('Orden de trabajo revertida por NC contabilizada', [
        'work_order_id' => $workOrder->id,
        'work_order_correlative' => $workOrder->correlative,
      ]);
    } catch (Exception $e) {
      Log::error('Error al revertir estado de orden de trabajo', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir movimiento de inventario de una cotización
   *
   * @param ApOrderQuotations $quotation
   * @return void
   */
  private function reverseInventoryForQuotation(ApOrderQuotations $quotation): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la cotización
      $movement = InventoryMovement::where('reference_type', ApOrderQuotations::class)
        ->where('reference_id', $quotation->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        $inventoryService = app(InventoryMovementService::class);
        $inventoryService->reverseStockFromMovement($movement->id);

        Log::info('Movimiento de inventario revertido para cotización', [
          'quotation_id' => $quotation->id,
          'movement_id' => $movement->id,
          'movement_number' => $movement->movement_number,
        ]);
      }
    } catch (Exception $e) {
      Log::error('Error al revertir inventario de cotización', [
        'quotation_id' => $quotation->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir movimiento de inventario de una orden de trabajo
   * Solo repuestos (ApWorkOrderParts), no mano de obra
   *
   * @param ApWorkOrder $workOrder
   * @return void
   */
  private function reverseInventoryForWorkOrder(ApWorkOrder $workOrder): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la orden de trabajo
      $movement = InventoryMovement::where('reference_type', ApWorkOrder::class)
        ->where('reference_id', $workOrder->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        $inventoryService = app(InventoryMovementService::class);
        $inventoryService->reverseStockFromMovement($movement->id);

        Log::info('Movimiento de inventario revertido para orden de trabajo', [
          'work_order_id' => $workOrder->id,
          'movement_id' => $movement->id,
          'movement_number' => $movement->movement_number,
        ]);
      }
    } catch (Exception $e) {
      Log::error('Error al revertir inventario de orden de trabajo', [
        'work_order_id' => $workOrder->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SyncAccountingStatusJob falló', ['error' => $exception->getMessage()]);
  }
}
