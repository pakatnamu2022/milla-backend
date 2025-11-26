<?php

namespace App\Jobs;

use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function json_encode;
use function str_pad;
use const STR_PAD_LEFT;

class VerifyAndMigrateShippingGuideJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5;
  public int $timeout = 300;
  public int $backoff = 60; // Esperar 60 segundos entre reintentos

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
    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId, $syncService);
      } else {
        $this->processAllPendingShippingGuides($syncService);
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
  protected function processAllPendingShippingGuides(DatabaseSyncService $syncService): void
  {
    $pendingGuides = ShippingGuides::whereIn('migration_status', [
      VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
      VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
    ])->get();

    foreach ($pendingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id, $syncService);
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
  protected function processShippingGuide(int $shippingGuideId, DatabaseSyncService $syncService): void
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
    if ($shippingGuide->migration_status === 'pending') {
      $shippingGuide->update(['migration_status' => 'in_progress']);
    }

    // Determinar si es una guía de venta o transferencia
    $isSale = $this->isSaleShippingGuide($shippingGuide);

    if ($isSale) {
      // Verificar guía de VENTA
      // 1. Verificar y actualizar estado de transacción de inventario (venta)
      $this->verifySaleInventoryTransaction($shippingGuide);

      // 2. Verificar y actualizar estado de detalle de transacción (venta)
      $this->verifySaleInventoryTransactionDetail($shippingGuide);

      // 3. Verificar y actualizar estado de serial de transacción (venta)
      $this->verifySaleInventoryTransactionSerial($shippingGuide);
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

    // Verificar en la BD intermedia
    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    Log::info('Resultado de la verificación en BD intermedia', [
      'shipping_guide_id' => $shippingGuide->id,
      'existing_transfer_found' => $existingTransfer ? true : false
    ]);

    if ($existingTransfer) {
      Log::info('Actualizando estado del log de transferencia', [
        'shipping_guide_id' => $shippingGuide->id,
        'proceso_estado' => $existingTransfer->ProcesoEstado ?? 0,
        'proceso_error' => $existingTransfer->ProcesoError ?? null
      ]);
      // Actualizar el log con el estado de la BD intermedia
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
        $vehicleMovementService->storeInventoryVehicleMovement($vehicle);
      }
    }
  }

  /**
   * Verifica el estado del detalle de transferencia en la BD intermedia
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

    // Verificar en la BD intermedia
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if ($existingDetail) {
      // Actualizar el log con el estado de la BD intermedia
      $detailLog->updateProcesoEstado(1);
    }
  }

  /**
   * Verifica el estado del serial de transferencia en la BD intermedia
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

    // Verificar en la BD intermedia
    $existingSerial = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
      ->first();

    if ($existingSerial) {
      $procesoEstado = $existingSerial->ProcesoEstado ?? 0;

      // Actualizar el log con el estado de la BD intermedia
      $serialLog->updateProcesoEstado(1);

      // Si Dynamics aceptó la transferencia (ProcesoEstado = 1), actualizar el warehouse_id del vehículo
      if ($procesoEstado === 1) {
        $this->updateVehicleWarehouse($shippingGuide);
      }
    }
  }

  /**
   * Verifica el estado de la transacción de inventario (VENTA) en la BD intermedia
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

      // Verificar en la BD intermedia
      $existingTransaction = DB::connection('dbtp')
        ->table('neInTbTransaccionInventario')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      if ($existingTransaction) {
        // Actualizar el log con el estado de la BD intermedia
        $transactionLog->updateProcesoEstado(
          $existingTransaction->ProcesoEstado ?? 0,
          $existingTransaction->ProcesoError ?? null
        );
      }
    }
  }

  /**
   * Verifica el estado del detalle de transacción de inventario (VENTA) en la BD intermedia
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

      // Verificar en la BD intermedia
      $existingDetail = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      if ($existingDetail) {
        // Actualizar el log con el estado de la BD intermedia
        $detailLog->updateProcesoEstado(
          $existingDetail->ProcesoEstado ?? 0,
          $existingDetail->ProcesoError ?? null
        );
      }
    }
  }

  /**
   * Verifica el estado del serial de transacción de inventario (VENTA) en la BD intermedia
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

      // Verificar en la BD intermedia
      $existingSerial = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
        ->first();

      if ($existingSerial) {
        // Actualizar el log con el estado de la BD intermedia
        $serialLog->updateProcesoEstado(
          $existingSerial->ProcesoEstado ?? 0,
          $existingSerial->ProcesoError ?? null
        );
      }
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
