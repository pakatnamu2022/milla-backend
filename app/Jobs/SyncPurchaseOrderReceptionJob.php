<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncPurchaseOrderReceptionJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 120;
  public int $backoff = 30; // Esperar 30 segundos entre reintentos

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $purchaseOrderId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $purchaseOrder = PurchaseOrder::with(['vehicleMovement.vehicle'])->find($this->purchaseOrderId);

    if (!$purchaseOrder) {
      // Log::error("Purchase order not found: {$this->purchaseOrderId}");
      return;
    }

    try {
      // Validar que la OC esté en estado 1 (procesada)
      $this->waitForPurchaseOrderSync($purchaseOrder);

      // Obtener los logs de migración
      $receptionLog = $this->getOrCreateLog(
        $purchaseOrder->id,
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION],
        $purchaseOrder->number_guide
      );

      $receptionDetailLog = $this->getOrCreateLog(
        $purchaseOrder->id,
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL],
        $purchaseOrder->number_guide
      );

      $receptionSerialLog = $this->getOrCreateLog(
        $purchaseOrder->id,
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL],
        $purchaseOrder->vehicleMovement?->vehicle?->vin
      );

      // Sincronizar la recepción (NI) Y sus detalles juntos
      $resource = new VehiclePurchaseOrderResource($purchaseOrder);
      $resourceData = $resource->toArray(request());

      $receptionLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception', $resourceData, 'create');
      $receptionLog->updateProcesoEstado(0);

      $receptionDetailLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception_det', $resourceData, 'create');
      $receptionDetailLog->updateProcesoEstado(0);

      $receptionSerialLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $resourceData, 'create');
      $receptionSerialLog->updateProcesoEstado(0);

      // Log::info("Purchase order reception with details synced successfully for PO: {$this->purchaseOrderId}");
    } catch (\Exception $e) {
      // Log::error("Failed to sync purchase order reception for PO {$this->purchaseOrderId}: {$e->getMessage()}");

      // Marcar los logs como fallidos
      $this->markLogsAsFailed($purchaseOrder->id, $e->getMessage());

      throw $e;
    }
  }

  /**
   * Verifica que la OC tenga ProcesoEstado = 1
   */
  protected function waitForPurchaseOrderSync(PurchaseOrder $purchaseOrder): void
  {
    $dbtp = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if (!$dbtp) {
      // Log::warning("OC {$purchaseOrder->number} no encontrada en tabla intermedia para PO ID: {$purchaseOrder->id}");
      throw new \Exception("OC no encontrada en tabla intermedia: {$purchaseOrder->number}");
    }

    if ($dbtp->ProcesoEstado != 1) {
      // Log::info("OC {$purchaseOrder->number} aún no procesada. ProcesoEstado: {$dbtp->ProcesoEstado}. El job se reintentará.");
      throw new \Exception("OC aún no procesada. ProcesoEstado: {$dbtp->ProcesoEstado}");
    }

    // Verificar si hay error
    if (!empty($dbtp->ProcesoError)) {
      // Log::error("Error en sincronización de la OC {$purchaseOrder->number}: {$dbtp->ProcesoError}");
      throw new \Exception("Error en sincronización de la OC: {$dbtp->ProcesoError}");
    }

    // Log::info("OC {$purchaseOrder->number} verificada exitosamente (ProcesoEstado = 1). Procediendo con la recepción.");
  }

  /**
   * Obtiene o crea un registro de log
   */
  protected function getOrCreateLog(int $purchaseOrderId, string $step, string $tableName, ?string $externalId = null): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'vehicle_purchase_order_id' => $purchaseOrderId,
        'step' => $step,
      ],
      [
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
        'external_id' => $externalId,
      ]
    );
  }

  /**
   * Marca los logs de recepción como fallidos
   */
  protected function markLogsAsFailed(int $purchaseOrderId, string $errorMessage): void
  {
    VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrderId)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION,
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL,
      ])
      ->delete();
  }

  public function failed(\Throwable $exception): void
  {
    // Log::error("Failed SyncPurchaseOrderReceptionJob definitivamente para PO ID {$this->purchaseOrderId} después de {$this->tries} intentos: {$exception->getMessage()}");

    // Marcar los logs como fallidos
    if ($this->purchaseOrderId) {
      $purchaseOrder = PurchaseOrder::find($this->purchaseOrderId);
      $errorMessage = "Job de recepción falló después de {$this->tries} intentos: {$exception->getMessage()}";

      if ($purchaseOrder) {
        // Log::error("OC {$purchaseOrder->number}: {$errorMessage}");

        // Actualizar estado de migración general
        $purchaseOrder->update(['migration_status' => 'failed']);
      }

      $this->markLogsAsFailed($this->purchaseOrderId, $errorMessage);
    }
  }
}
