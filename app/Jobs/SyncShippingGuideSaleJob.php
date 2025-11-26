<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\ShippingGuideDetailDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideHeaderDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideSeriesDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncShippingGuideSaleJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 180;
  public int $backoff = 30;

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
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      $this->processShippingGuideSale($this->shippingGuideId, $syncService);
    } catch (\Exception $e) {
      Log::error('Error en SyncShippingGuideSaleJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Procesa una guía de remisión de venta específica
   */
  protected function processShippingGuideSale(int $shippingGuideId, DatabaseSyncService $syncService): void
  {
    $shippingGuide = ShippingGuides::with([
      'vehicleMovement.vehicle',
      'sedeTransmitter',
      'sedeReceiver'
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      Log::error('Guía de remisión no encontrada', ['id' => $shippingGuideId]);
      return;
    }

    // Determinar si la guía está cancelada
    $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;

    // VERIFICACIONES (Comentadas para implementar después)
    // Aquí se pueden agregar verificaciones antes de sincronizar:
    // - Verificar que exista el vehículo en Dynamics
    // - Verificar que existan los almacenes origen/destino
    // - Verificar que la guía esté en estado válido
    // Ejemplo:
    // if (!$this->verifyVehicleExists($shippingGuide)) {
    //     throw new \Exception('El vehículo no existe en Dynamics');
    // }

    Log::info('Iniciando sincronización de guía de remisión de venta', [
      'shipping_guide_id' => $shippingGuide->id,
      'is_cancelled' => $isCancelled,
    ]);

    // 1. Sincronizar transacción de inventario (cabecera)
    $this->syncInventoryTransaction($shippingGuide, $syncService, $isCancelled);

    Log::info('Sincronización de transacción de inventario completada', [
      'shipping_guide_id' => $shippingGuide->id,
    ]);

    // 2. Sincronizar detalle de transacción de inventario
    $this->syncInventoryTransactionDetail($shippingGuide, $syncService, $isCancelled);

    Log::info('Sincronización de detalle de transacción de inventario completada', [
      'shipping_guide_id' => $shippingGuide->id,
    ]);

    // 3. Sincronizar serial de transacción de inventario (VIN)
    $this->syncInventoryTransactionSerial($shippingGuide, $syncService, $isCancelled);

    // Nota: La verificación de completitud se hace en VerifyAndMigrateShippingGuideJob
    // que se ejecuta periódicamente cada 30 segundos
  }

  /**
   * Sincroniza la cabecera de transacción de inventario (venta)
   */
  protected function syncInventoryTransaction(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE;

    $transactionLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Transformar datos usando el Resource
      $resource = new ShippingGuideHeaderDynamicsResource($shippingGuide);
      $data = $resource->toArray(request());

      // Sincronizar cabecera de transacción de inventario
      $transactionLog->markAsInProgress();
      $syncService->sync('inventory_transaction', $data, 'create');
      $transactionLog->updateProcesoEstado(0); // 0 = En proceso en la BD intermedia
    } catch (\Exception $e) {
      $transactionLog->markAsFailed("Error al sincronizar transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el serial (VIN) de transacción de inventario
   */
  protected function syncInventoryTransactionSerial(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL;

    $transactionSerialLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transactionSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Transformar datos usando el Resource
      $resource = new ShippingGuideSeriesDynamicsResource($shippingGuide);
      $data = $resource->toArray(request());

      // Sincronizar serial de transacción de inventario
      $transactionSerialLog->markAsInProgress();
      $syncService->sync('inventory_transaction_dts', $data, 'create');
      $transactionSerialLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transactionSerialLog->markAsFailed("Error al sincronizar serial de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transacción de inventario
   */
  protected function syncInventoryTransactionDetail(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    if (!$vehicle_vn_id) {
      throw new \Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
    }

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL;

    $transactionDetailLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transactionDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $vehicleVn = Vehicles::findOrFail($vehicle_vn_id);

      // Transformar datos usando el Resource
      $resource = new ShippingGuideDetailDynamicsResource($vehicleVn, $shippingGuide);
      $data = $resource->toArray(request());

      // Sincronizar detalle de transacción de inventario
      $transactionDetailLog->markAsInProgress();
      $syncService->sync('inventory_transaction_dt', $data, 'create');
      $transactionDetailLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transactionDetailLog->markAsFailed("Error al sincronizar detalle de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Obtiene o crea un registro de log
   */
  protected function getOrCreateLog(int $shippingGuideId, string $step, string $tableName, ?string $externalId = null, ?int $vehicleId = null): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'shipping_guide_id' => $shippingGuideId,
        'step' => $step,
      ],
      [
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
        'external_id' => $externalId,
        'ap_vehicles_id' => $vehicleId,
      ]
    );
  }



  /**
   * MÉTODOS DE VERIFICACIÓN (Para implementar después)
   */

  // /**
  //  * Verifica que el vehículo exista en Dynamics
  //  */
  // protected function verifyVehicleExists(ShippingGuides $shippingGuide): bool
  // {
  //     $vehicle = $shippingGuide->vehicleMovement?->vehicle;
  //     if (!$vehicle) {
  //         return false;
  //     }
  //
  //     $existsInDynamics = DB::connection('dbtp')
  //         ->table('neInTbArticulo')
  //         ->where('EmpresaId', Company::AP_DYNAMICS)
  //         ->where('Articulo', $vehicle->model->code)
  //         ->exists();
  //
  //     return $existsInDynamics;
  // }

  // /**
  //  * Verifica que los almacenes existan en Dynamics
  //  */
  // protected function verifyWarehousesExist(ShippingGuides $shippingGuide): bool
  // {
  //     // Verificar almacén origen
  //     $originExists = DB::connection('dbtp')
  //         ->table('neInTbAlmacen')
  //         ->where('EmpresaId', Company::AP_DYNAMICS)
  //         ->where('AlmacenId', $shippingGuide->sedeTransmitter->warehouse_code)
  //         ->exists();
  //
  //     // Verificar almacén destino
  //     $destinationExists = DB::connection('dbtp')
  //         ->table('neInTbAlmacen')
  //         ->where('EmpresaId', Company::AP_DYNAMICS)
  //         ->where('AlmacenId', $shippingGuide->sedeReceiver->warehouse_code)
  //         ->exists();
  //
  //     return $originExists && $destinationExists;
  // }

  public function failed(\Throwable $exception): void
  {
    Log::error('SyncShippingGuideSaleJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
    ]);
  }
}
