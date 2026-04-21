<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
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
            $this->restoreVehicleToInventoryIfApplicable($document);
            $this->createInventoryMovementIfApplicable($document);
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

  public function failed(Throwable $exception): void
  {
    Log::error('SyncAccountingStatusJob falló', ['error' => $exception->getMessage()]);
  }
}
