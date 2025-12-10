<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\AccountingEntryHeaderDynamicsResource;
use App\Http\Services\ap\facturacion\AccountingEntryService;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAccountingEntryJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 180;
  public int $backoff = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $shippingGuideId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   */
  public function handle(
    DatabaseSyncService    $syncService,
    AccountingEntryService $accountingService
  ): void
  {
    try {
      Log::info('Iniciando sincronización de asiento contable', [
        'shipping_guide_id' => $this->shippingGuideId
      ]);

      // 1. Cargar ShippingGuide con relaciones necesarias
      $shippingGuide = ShippingGuides::with([
        'vehicleMovement.vehicle.model.classArticle',
        'vehicleMovement',
      ])->find($this->shippingGuideId);

      if (!$shippingGuide) {
        Log::warning('ShippingGuide no encontrada', [
          'shipping_guide_id' => $this->shippingGuideId
        ]);
        return;
      }

      // 2. Validar que sea guía de VENTA
      if ($shippingGuide->transfer_reason_id !== SunatConcepts::TRANSFER_REASON_VENTA) {
        Log::info('ShippingGuide no es de venta, saltando sincronización de asientos', [
          'shipping_guide_id' => $shippingGuide->id,
          'transfer_reason_id' => $shippingGuide->transfer_reason_id
        ]);
        return;
      }

      // 3. Obtener ElectronicDocument desde VehicleMovement
      $electronicDocument = ElectronicDocument::with([
        'items',
        'creator.person',
        'currency',
        'seriesModel.sede',
        'vehicleMovement.vehicle.model.classArticle',
        'vehicle',
      ])
        ->where('is_advance_payment', 0)
        ->whereHas('vehicle', function ($query) use ($shippingGuide) {
          $query->where('vin', $shippingGuide->vehicleMovement->vehicle->vin);
        })
        ->first();

      if (!$electronicDocument) {
        Log::warning('No se encontró factura asociada a la guía', [
          'shipping_guide_id' => $shippingGuide->id,
          'vehicle_movement_id' => $shippingGuide->vehicle_movement_id
        ]);
        return;
      }

      // 4. Validar que tenga items
      if ($electronicDocument->items->count() === 0) {
        Log::warning('La factura no tiene items', [
          'shipping_guide_id' => $shippingGuide->id,
          'electronic_document_id' => $electronicDocument->id
        ]);
        return;
      }

      // 5. Crear/recuperar logs de migración
      $headerLog = $this->getOrCreateLog(
        $shippingGuide->id,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
        'neInTbIntegracionAsientoCab',
        $electronicDocument->full_number
      );

      $detailLog = $this->getOrCreateLog(
        $shippingGuide->id,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
        'neInTbIntegracionAsientoDet',
        $electronicDocument->full_number
      );

      // 6. Generar número de asiento
      $asientoNumber = $accountingService->getNextAsientoNumber();

      Log::info('Número de asiento generado', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber
      ]);

      // 7. Sincronizar cabecera
      $headerLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);

      $headerResource = new AccountingEntryHeaderDynamicsResource($electronicDocument, $shippingGuide->issue_date, $asientoNumber);
      $headerData = $headerResource->toArray(request());

      $syncService->sync('accounting_entry_header', $headerData, 'create');
      $headerLog->update(['proceso_estado' => 0]);

      Log::info('Cabecera de asiento sincronizada', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber
      ]);

      // 8. Generar y sincronizar líneas de detalle
      $detailLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);

      $lines = $accountingService->generateAccountingLines($electronicDocument, $asientoNumber);

      Log::info('Líneas de detalle generadas', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber,
        'total_lines' => count($lines)
      ]);

      // Validar balance antes de sincronizar
      $accountingService->validateBalance($lines);

      // Sincronizar cada línea
      foreach ($lines as $line) {
        $syncService->sync('accounting_entry_detail', $line, 'create');
      }

      $detailLog->update(['proceso_estado' => 0]);

      Log::info('Asiento contable sincronizado exitosamente', [
        'shipping_guide_id' => $shippingGuide->id,
        'asiento_number' => $asientoNumber,
        'total_lines' => count($lines),
        'electronic_document' => $electronicDocument->full_number
      ]);

      // Marcar logs como completados
      $headerLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);
      $detailLog->update(['status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED]);

    } catch (Exception $e) {
      Log::error('Error en SyncAccountingEntryJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      throw $e;
    }
  }

  /**
   * Obtiene o crea un log de migración
   */
  protected function getOrCreateLog(
    int    $shippingGuideId,
    string $step,
    string $tableName,
    string $externalId
  ): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'shipping_guide_id' => $shippingGuideId,
        'step' => $step,
      ],
      [
        'table_name' => $tableName,
        'external_id' => $externalId,
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'attempts' => 0,
      ]
    );
  }

  /**
   * Handle a job failure.
   */
  public function failed(Throwable $exception): void
  {
    Log::error('SyncAccountingEntryJob failed definitivamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);

    // Marcar logs como failed
    VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $this->shippingGuideId)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
        VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
      ])
      ->update([
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
        'error_message' => $exception->getMessage(),
      ]);
  }
}
