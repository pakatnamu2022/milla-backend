<?php

namespace App\Jobs;

use App\Http\Resources\Dynamics\ShippingGuideDetailDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideHeaderDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideSeriesDynamicsResource;
use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use function str_pad;
use const STR_PAD_LEFT;

class VerifyAndMigrateShippingGuideJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5;
  public int $timeout = 300;
  public int $backoff = 60; // Esperar 60 segundos entre reintentos

  protected DatabaseSyncService $syncService;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $shippingGuideId = null
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa guía
   * Si no, procesa todas las guías no migradas
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $this->syncService = $syncService;

    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId);
      } else {
        $this->processAllPendingShippingGuides();
      }
    } catch (\Exception $e) {
      Log::error('Error en VerifyAndMigrateShippingGuideJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Procesa todas las guías de remisión pendientes de migración
   */
  protected function processAllPendingShippingGuides(): void
  {
    $pendingGuides = ShippingGuides::whereIn('migration_status', [
      VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
      VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
    ])->get();

    foreach ($pendingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id);
      } catch (\Exception $e) {
        Log::error('Error procesando guía de remisión', [
          'shipping_guide_id' => $guide->id,
          'error' => $e->getMessage(),
        ]);
        continue;
      }
    }
  }

  /**
   * Procesa una guía de remisión específica
   */
  protected function processShippingGuide(int $shippingGuideId): void
  {
    $shippingGuide = ShippingGuides::with([
      'vehicleMovement.vehicle.model',
      'sedeTransmitter',
      'sedeReceiver'
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      return;
    }

    // Actualizar estado general a 'in_progress' si está pending
    if ($shippingGuide->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      $shippingGuide->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);
    }

    Log::info('Iniciando verificación de guía de remisión', ['shipping_guide_id' => $shippingGuide->id]);
    // Determinar si es una guía de venta o transferencia
    $isSale = $this->isSaleShippingGuide($shippingGuide);
    Log::info('Tipo de guía de remisión determinado', [
      'shipping_guide_id' => $shippingGuide->id,
      'is_sale' => $isSale
    ]);

    if ($isSale) {
      // Verificar guía de VENTA
      // 1. Verificar y actualizar estado de transacción de inventario (venta)
      Log::info('Guía de remisión es de venta, procediendo con verificación de venta', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reason_id' => $shippingGuide->transfer_reason_id
      ]);
      $this->verifySaleInventoryTransaction($shippingGuide);
      Log::info('Verificación de venta completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);

      // 2. Verificar y actualizar estado de detalle de transacción (venta)
      $this->verifySaleInventoryTransactionDetail($shippingGuide);
      Log::info('Verificación de detalle de venta completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);

      // 3. Verificar y actualizar estado de serial de transacción (venta)
      $this->verifySaleInventoryTransactionSerial($shippingGuide);
      Log::info('Verificación de serial de venta completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);
    } else {
      Log::info('Guía de remisión no es de venta, procediendo con verificación de transferencia', [
        'shipping_guide_id' => $shippingGuide->id,
        'transfer_reason_id' => $shippingGuide->transfer_reason_id
      ]);
      // Verificar guía de TRANSFERENCIA
      // 1. Verificar y actualizar estado de transferencia de inventario
      $this->verifyInventoryTransfer($shippingGuide);

      Log::info('Verificación de transferencia completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);

      // 2. Verificar y actualizar estado de detalle de transferencia
      $this->verifyInventoryTransferDetail($shippingGuide);

      Log::info('Verificación de detalle de transferencia completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);

      // 3. Verificar y actualizar estado de serial de transferencia
      $this->verifyInventoryTransferSerial($shippingGuide);
      Log::info('Verificación de serial de transferencia completada para guía de remisión', [
        'shipping_guide_id' => $shippingGuide->id
      ]);
    }

    // 4. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($shippingGuide);
  }

  /**
   * Verifica el estado de la transferencia de inventario en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifyInventoryTransfer(ShippingGuides $shippingGuide): void
  {
    $transferLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER)
      ->first();

    Log::info('Iniciando verificación de transferencia de inventario', [
      'shipping_guide_id' => $shippingGuide->id,
      'transfer_log_found' => $transferLog ? true : false
    ]);

    if (!$transferLog) {
      return;
    }

    Log::info('Transfer log encontrado', [
      'shipping_guide_id' => $shippingGuide->id,
      'transfer_log_status' => $transferLog->status
    ]);

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    Log::info('Verificando en la base de datos intermedia', [
      'shipping_guide_id' => $shippingGuide->id,
      'transfer_id' => $shippingGuide->dyn_series
    ]);

    // NUEVO: Verificar si existe en la BD intermedia
    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    Log::info('Resultado de la verificación en BD intermedia', [
      'shipping_guide_id' => $shippingGuide->id,
      'existing_transfer_found' => $existingTransfer ? true : false
    ]);

    if (!$existingTransfer) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransfer($shippingGuide, $isCancelled);
      return;
    }

    // EXISTE → ACTUALIZAR ESTADO
    Log::info('Actualizando estado del log de transferencia', [
      'shipping_guide_id' => $shippingGuide->id,
      'proceso_estado' => $existingTransfer->ProcesoEstado ?? 0,
      'proceso_error' => $existingTransfer->ProcesoError ?? null
    ]);

    $transferLog->updateProcesoEstado(
      $existingTransfer->ProcesoEstado ?? 0,
      $existingTransfer->ProcesoError ?? null
    );

    Log::info('Estado del log de transferencia actualizado', [
      'shipping_guide_id' => $shippingGuide->id,
      'log_status' => $transferLog->status,
      'proceso_estado' => $transferLog->proceso_estado
    ]);

    if ($transferLog->proceso_estado === 1) {
      $vehicle = $shippingGuide->vehicleMovement?->vehicle;
      if (!$vehicle) {
        throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
      }

      $vehicleMovementService = new VehicleMovementService();
      $vehicleMovementService->storeInventoryVehicleMovement($vehicle->id);
    }
  }

  /**
   * Verifica el estado del detalle de transferencia en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifyInventoryTransferDetail(ShippingGuides $shippingGuide): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL)
      ->first();

    if (!$detailLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // NUEVO: Verificar si existe en la BD intermedia
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingDetail) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransferSerial($shippingGuide, $isCancelled);
      return;
    }

    // EXISTE → ACTUALIZAR ESTADO
    $detailLog->updateProcesoEstado(1);
  }

  /**
   * Verifica el estado del serial de transferencia en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifyInventoryTransferSerial(ShippingGuides $shippingGuide): void
  {
    $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL)
      ->first();

    if (!$serialLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // NUEVO: Verificar si existe en la BD intermedia
    $existingSerial = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
      ->first();

    if (!$existingSerial) {
      // NO EXISTE → SINCRONIZAR
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncInventoryTransferDetail($shippingGuide, $isCancelled);
      return;
    }

    // EXISTE → ACTUALIZAR ESTADO
    $procesoEstado = $existingSerial->ProcesoEstado ?? 0;
    $serialLog->updateProcesoEstado(1);

    // Si Dynamics aceptó la transferencia (ProcesoEstado = 1), actualizar el warehouse_id del vehículo
    if ($procesoEstado === 1) {
      $this->updateVehicleWarehouse($shippingGuide);
    }
  }

  /**
   * Verifica el estado de la transacción de inventario (VENTA) en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifySaleInventoryTransaction(ShippingGuides $shippingGuide): void
  {
    // Verificar tanto la versión normal como la reversión
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL
    ];

    foreach ($steps as $step) {
      $transactionLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$transactionLog) {
        continue;
      }

      // Si ya está completado, no hacer nada
      if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      // Construir el TransaccionId
      $transactionId = $this->buildSaleTransactionId($shippingGuide, $step);

      // NUEVO: Verificar si existe en la BD intermedia
      $existingTransaction = DB::connection('dbtp')
        ->table('neInTbTransaccionInventario')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      if (!$existingTransaction) {
        // NO EXISTE → SINCRONIZAR
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncSaleInventoryTransaction($shippingGuide, $isCancelled);
        continue;
      }

      // EXISTE → ACTUALIZAR ESTADO
      $transactionLog->updateProcesoEstado(
        $existingTransaction->ProcesoEstado ?? 0,
        $existingTransaction->ProcesoError ?? null
      );
    }
  }

  /**
   * Verifica el estado del detalle de transacción de inventario (VENTA) en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifySaleInventoryTransactionDetail(ShippingGuides $shippingGuide): void
  {
    // Verificar tanto la versión normal como la reversión
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL
    ];

    foreach ($steps as $step) {
      $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$detailLog) {
        continue;
      }

      // Si ya está completado, no hacer nada
      if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      // Construir el TransaccionId
      $transactionId = $this->buildSaleTransactionId($shippingGuide, $step);

      // NUEVO: Verificar si existe en la BD intermedia
      $existingDetail = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      Log::info('Verificación de detalle de transacción de inventario', [
        'transaction_id' => $transactionId,
        'shipping_guide_id' => $shippingGuide->id,
        'step' => $step,
        'existing_detail_found' => $existingDetail ? true : false
      ]);

      if (!$existingDetail) {
        // NO EXISTE → SINCRONIZAR
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncSaleInventoryTransactionDetail($shippingGuide, $isCancelled);
        continue;
      }

      // EXISTE → ACTUALIZAR ESTADO
      $detailLog->updateProcesoEstado(1);
    }
  }

  /**
   * Verifica el estado del serial de transacción de inventario (VENTA) en la BD intermedia
   * Si no existe, la sincroniza
   */
  protected function verifySaleInventoryTransactionSerial(ShippingGuides $shippingGuide): void
  {
    // Verificar tanto la versión normal como la reversión
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL
    ];

    foreach ($steps as $step) {
      $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$serialLog) {
        continue;
      }

      // Si ya está completado, no hacer nada
      if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      // Construir el TransaccionId
      $transactionId = $this->buildSaleTransactionId($shippingGuide, $step);

      // NUEVO: Verificar si existe en la BD intermedia
      $existingSerial = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
        ->first();

      if (!$existingSerial) {
        // NO EXISTE → SINCRONIZAR
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncSaleInventoryTransactionSerial($shippingGuide, $isCancelled);
        continue;
      }

      // EXISTE → ACTUALIZAR ESTADO
      $serialLog->updateProcesoEstado(1);
    }
  }

  /**
   * Determina si una guía de remisión es de venta
   */
  protected function isSaleShippingGuide(ShippingGuides $shippingGuide): bool
  {
    // transfer_reason_id = 1 es venta (SunatConcepts::TRANSFER_REASON_VENTA)
    return $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA;
  }

  /**
   * Construye el TransaccionId para guías de venta
   */
  protected function buildSaleTransactionId(ShippingGuides $shippingGuide, string $step): string
  {
    // Determinar el prefijo según el tipo de guía
    $prefix = $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA ? 'TVEN-' : 'TSAL-';

    // Construir el TransaccionId base
    $transactionId = $prefix . str_pad($shippingGuide->correlative, 8, '0', STR_PAD_LEFT);

    // Si es una reversión, agregar asterisco
    if (str_contains($step, 'REVERSAL')) {
      $transactionId .= '*';
    }

    return $transactionId;
  }

  /**
   * Verifica si todos los pasos están completos y actualiza el estado general
   */
  protected function checkAndUpdateCompletionStatus(ShippingGuides $shippingGuide): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted && $logs->count() === 3) { // 3 pasos en total para shipping guides
      // Marcar la guía como sincronizada
      $shippingGuide->update([
        'status_dynamic' => 1,
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
      Log::info('Guía de remisión sincronizada completamente', ['id' => $shippingGuide->id]);
    } elseif ($hasFailed) {
      $shippingGuide->update([
        'status_dynamic' => 0,
        'migration_status' => 'failed',
      ]);
      Log::warning('Falló la sincronización de guía de remisión', ['id' => $shippingGuide->id]);
    }
  }

  /**
   * Actualiza el warehouse_id del vehículo después de que Dynamics acepta la transferencia
   */
  protected function updateVehicleWarehouse(ShippingGuides $shippingGuide): void
  {
    try {
      $vehicle = $shippingGuide->vehicleMovement?->vehicle;

      if (!$vehicle) {
        Log::warning('No se encontró vehículo asociado a la guía de remisión', [
          'shipping_guide_id' => $shippingGuide->id
        ]);
        return;
      }

      // Determinar el almacén de destino según el tipo de transferencia
      $warehouseId = null;

      if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_COMPRA) {
        // Para COMPRA: mover al almacén de STOCK (is_received = true) de la sede receptora
        $warehouseId = Warehouse::where('sede_id', $shippingGuide->sedeReceiver->id)
          ->where('type_operation_id', $vehicle->type_operation_id)
          ->where('article_class_id', $vehicle->model->class_id)
          ->where('is_received', true)
          ->value('id');

      } elseif ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        // Para TRASLADO ENTRE SEDES: mover al almacén de STOCK (is_received = true) de la sede receptora
        $warehouseId = Warehouse::where('sede_id', $shippingGuide->sedeReceiver->id)
          ->where('type_operation_id', $vehicle->type_operation_id)
          ->where('article_class_id', $vehicle->model->class_id)
          ->where('is_received', true)
          ->value('id');

      } else {
        // Para otros motivos: usar almacén de destino de la sede receptora
        $warehouseId = Warehouse::where('sede_id', $shippingGuide->sedeReceiver->id)
          ->where('type_operation_id', $vehicle->type_operation_id)
          ->where('article_class_id', $vehicle->model->class_id)
          ->where('is_received', true)
          ->value('id');
      }

      if ($warehouseId) {
        $oldWarehouseId = $vehicle->warehouse_id;
        $vehicle->update(['warehouse_id' => $warehouseId]);

        Log::info('warehouse_id del vehículo actualizado después de confirmación de Dynamics', [
          'vehicle_id' => $vehicle->id,
          'vin' => $vehicle->vin,
          'shipping_guide_id' => $shippingGuide->id,
          'old_warehouse_id' => $oldWarehouseId,
          'new_warehouse_id' => $warehouseId,
          'transfer_reason' => $shippingGuide->transfer_reason_id
        ]);
      } else {
        Log::warning('No se encontró almacén de destino para actualizar warehouse_id', [
          'vehicle_id' => $vehicle->id,
          'shipping_guide_id' => $shippingGuide->id,
          'sede_receiver_id' => $shippingGuide->sedeReceiver->id,
          'type_operation_id' => $vehicle->type_operation_id,
          'article_class_id' => $vehicle->model->class_id
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error al actualizar warehouse_id del vehículo', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * ==========================================================================
   * MÉTODOS DE SINCRONIZACIÓN PARA GUÍAS DE VENTA
   * ==========================================================================
   */

  /**
   * Sincroniza la cabecera de transacción de inventario (venta)
   */
  protected function syncSaleInventoryTransaction(ShippingGuides $shippingGuide, bool $isCancelled): void
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
      $this->syncService->sync('inventory_transaction', $data, 'create');
      $transactionLog->updateProcesoEstado(0); // 0 = En proceso en la BD intermedia
    } catch (\Exception $e) {
      $transactionLog->markAsFailed("Error al sincronizar transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transacción de inventario (venta)
   */
  protected function syncSaleInventoryTransactionDetail(ShippingGuides $shippingGuide, bool $isCancelled): void
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
      $this->syncService->sync('inventory_transaction_dt', $data, 'create');
      $transactionDetailLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transactionDetailLog->markAsFailed("Error al sincronizar detalle de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el serial (VIN) de transacción de inventario (venta)
   */
  protected function syncSaleInventoryTransactionSerial(ShippingGuides $shippingGuide, bool $isCancelled): void
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
      $this->syncService->sync('inventory_transaction_dts', $data, 'create');
      $transactionSerialLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transactionSerialLog->markAsFailed("Error al sincronizar serial de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * ==========================================================================
   * MÉTODOS DE SINCRONIZACIÓN PARA GUÍAS DE TRANSFERENCIA
   * ==========================================================================
   */

  /**
   * Sincroniza la cabecera de transferencia de inventario
   */
  protected function syncInventoryTransfer(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;
    $prefix = $this->getTransferPrefix($shippingGuide);

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER;

    $transferLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = $prefix . str_pad($shippingGuide->correlative, 10, '0', STR_PAD_LEFT);
      if ($isCancelled) {
        $transferId .= '*';
      }

      // Preparar datos para sincronización del detalle
      $data = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'FechaEmision' => $shippingGuide->received_date->format('Y-m-d'),
        'FechaContable' => $shippingGuide->received_date->format('Y-m-d'),
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => '',
        'FechaProceso' => now()->format('Y-m-d H:i:s'),
      ];

      // Sincronizar cabecera de transferencia
      $transferLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer', $data, 'create');
      $transferLog->updateProcesoEstado(0); // 0 = En proceso en la BD intermedia

      // Actualizar dyn_series en ShippingGuides con el TransferenciaId
      $shippingGuide->update([
        'dyn_series' => $transferId,
      ]);
    } catch (\Exception $e) {
      $transferLog->markAsFailed("Error al sincronizar transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transferencia de inventario
   */
  protected function syncInventoryTransferDetail(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    if (!$vehicle_vn_id) {
      throw new \Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
    }

    $prefix = $this->getTransferPrefix($shippingGuide);
    $transferIdOriginal = $prefix . str_pad($shippingGuide->correlative, 10, '0', STR_PAD_LEFT);
    $transferIdFormatted = $transferIdOriginal;

    // Si está cancelada, agregar asterisco al final del TransferenciaId
    if ($isCancelled) {
      $transferIdFormatted .= '*';
    }

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL;

    $transferSerialLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transferSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $vehicleVn = Vehicles::findOrFail(
        $shippingGuide->vehicleMovement?->vehicle?->id
        ?? throw new \Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.")
      );

      $type_operation_id = $vehicleVn->type_operation_id ?? null;
      $class_id = $vehicleVn->model->class_id ?? null;

      // Lógica diferenciada según el tipo de operación
      if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_COMPRA) {
        $sede_id = $shippingGuide->sedeReceiver->id ?? null;

        $baseQuery = Warehouse::where('sede_id', $sede_id)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('status', true); // Activo

        $warehouseStart = (clone $baseQuery)->where('is_received', false);
        $warehouseEnd = (clone $baseQuery)->where('is_received', true);

        $warehouseStartCode = $warehouseStart->value('dyn_code');
        $warehouseEndCode = $warehouseEnd->value('dyn_code');

        // Si está cancelada, invertir los almacenes
        if ($isCancelled) {
          $temp = $warehouseStartCode;
          $warehouseStartCode = $warehouseEndCode;
          $warehouseEndCode = $temp;
        }

        $sede = Sede::findOrFail($sede_id)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $inventoryAccount = $warehouseStart->value('inventory_account') ?
          $warehouseStart->value('inventory_account') . '-' . $sede : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $warehouseEnd->value('inventory_account') ?
          $warehouseEnd->value('inventory_account') . '-' . $sede : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          $tempAccount = $inventoryAccount;
          $inventoryAccount = $counterpartInventoryAccount;
          $counterpartInventoryAccount = $tempAccount;
        }

      } elseif ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        $sedeTransmitterId = $shippingGuide->sedeTransmitter->id ?? null;
        $sedeReceiverId = $shippingGuide->sedeReceiver->id ?? null;

        $transmitterQuery = Warehouse::where('sede_id', $sedeTransmitterId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', true)
          ->where('status', true); // Activo

        $receiverQuery = Warehouse::where('sede_id', $sedeReceiverId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', true)
          ->where('status', true); // Activo

        $sedeStart = Sede::findOrFail($sedeTransmitterId)->dyn_code ?? throw new Exception('La Sede transmisora no fue encontrada.');
        $sedeEnd = Sede::findOrFail($sedeReceiverId)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $warehouseStartCode = $transmitterQuery->value('dyn_code');
        $warehouseEndCode = $receiverQuery->value('dyn_code');

        // Si está cancelada, invertir los almacenes (retorna al almacén anterior)
        if ($isCancelled) {
          $temp = $warehouseStartCode;
          $warehouseStartCode = $warehouseEndCode;
          $warehouseEndCode = $temp;
        }

        $inventoryAccount = $transmitterQuery->value('inventory_account') ?
          $transmitterQuery->value('inventory_account') . '-' . $sedeStart : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $receiverQuery->value('inventory_account') ?
          $receiverQuery->value('inventory_account') . '-' . $sedeEnd : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          $tempAccount = $inventoryAccount;
          $inventoryAccount = $counterpartInventoryAccount;
          $counterpartInventoryAccount = $tempAccount;
        }

      } else {
        // Otro motivo: usar lógica por defecto (similar a COMPRA)
        $sede_id = $shippingGuide->sedeReceiver->id ?? null;

        $baseQuery = Warehouse::where('sede_id', $sede_id)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('status', true); // Activo

        $warehouseStartCode = (clone $baseQuery)->where('is_received', true)->value('dyn_code');
        $warehouseEndCode = (clone $baseQuery)->where('is_received', false)->value('dyn_code');

        // Si está cancelada, invertir los almacenes
        if ($isCancelled) {
          $temp = $warehouseStartCode;
          $warehouseStartCode = $warehouseEndCode;
          $warehouseEndCode = $temp;
        }

        $sede = Sede::findOrFail($sede_id)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $inventoryAccount = $baseQuery->where('is_received', true)->value('inventory_account') ?
          $baseQuery->where('is_received', true)->value('inventory_account') . '-' . $sede : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $baseQuery->where('is_received', false)->value('inventory_account') ?
          $baseQuery->where('is_received', false)->value('inventory_account') . '-' . $sede : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          $tempAccount = $inventoryAccount;
          $inventoryAccount = $counterpartInventoryAccount;
          $counterpartInventoryAccount = $tempAccount;
        }
      }

      $serialData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferIdFormatted,
        'Linea' => 1,
        'ArticuloId' => $shippingGuide->vehicleMovement?->vehicle?->model->code ?? 'N/A',
        'Motivo' => '',
        'UnidadMedidaId' => 'UND',
        'Cantidad' => 1,
        'AlmacenId_Ini' => $warehouseStartCode ?? throw new Exception('El Almacén de inicio no fue encontrado.'),
        'AlmacenId_Fin' => $warehouseEndCode ?? throw new Exception('El Almacén de fin no fue encontrado.'),
        'CuentaInventario' => $inventoryAccount ?? throw new Exception('La Cuenta de Inventario no fue encontrada.'),
        'CuentaContrapartida' => $counterpartInventoryAccount ?? throw new Exception('La Cuenta Contrapartida no fue encontrada.'),
      ];

      // Sincronizar serial de transferencia
      $transferSerialLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer_dt', $serialData, 'create');
      $transferSerialLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transferSerialLog->markAsFailed("Error al sincronizar serial de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el serial (VIN) de transferencia de inventario
   */
  protected function syncInventoryTransferSerial(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;
    $prefix = $this->getTransferPrefix($shippingGuide);

    // Si está cancelada, usar el step de reversión para crear un nuevo log
    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL;

    $transferDetailLog = $this->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    // Si ya está completado, no hacer nada (para este step específico)
    if ($transferDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar TransferenciaId con asterisco si está cancelada
      $transferId = $prefix . str_pad($shippingGuide->correlative, 10, '0', STR_PAD_LEFT);
      if ($isCancelled) {
        $transferId .= '*';
      }

      // Preparar datos para sincronización del detalle
      $detailData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'Linea' => 1,
        'Serie' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
        'ArticuloId' => $shippingGuide->vehicleMovement->vehicle->model->code ?? "N/A",
        'DatoUsuario1' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
        'DatoUsuario2' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
      ];

      // Sincronizar detalle de transferencia
      $transferDetailLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer_dts', $detailData, 'create');
      $transferDetailLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transferDetailLog->markAsFailed("Error al sincronizar detalle de transferencia: {$e->getMessage()}");
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

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateShippingGuideJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
    ]);
  }

  private function getTransferPrefix(ShippingGuides $shippingGuide): string
  {
    if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_COMPRA) {
      return 'CREC-';
    }

    if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
      return 'CTRA-';
    }

    return '-';
  }
}
