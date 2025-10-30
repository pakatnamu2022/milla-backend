<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncShippingGuideJob implements ShouldQueue
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
      $this->processShippingGuide($this->shippingGuideId, $syncService);
    } catch (\Exception $e) {
      Log::error('Error en SyncShippingGuideJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Procesa una guía de remisión específica
   */
  protected function processShippingGuide(int $shippingGuideId, DatabaseSyncService $syncService): void
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

    // VERIFICACIONES (Comentadas para implementar después)
    // Aquí se pueden agregar verificaciones antes de sincronizar:
    // - Verificar que exista el vehículo en Dynamics
    // - Verificar que existan los almacenes origen/destino
    // - Verificar que la guía esté en estado válido
    // Ejemplo:
    // if (!$this->verifyVehicleExists($shippingGuide)) {
    //     throw new \Exception('El vehículo no existe en Dynamics');
    // }

    // 1. Sincronizar transferencia de inventario (cabecera)
    $this->syncInventoryTransfer($shippingGuide, $syncService);

    // 2. Sincronizar detalle de transferencia
    $this->syncInventoryTransferDetail($shippingGuide, $syncService);

    // 3. Sincronizar serial de transferencia (VIN)
    $this->syncInventoryTransferSerial($shippingGuide, $syncService);

    // 4. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($shippingGuide);
  }

  /**
   * Sincroniza la cabecera de transferencia de inventario
   */
  protected function syncInventoryTransfer(ShippingGuides $shippingGuide, DatabaseSyncService $syncService): void
  {
    $transferLog = $this->getOrCreateLog(
      $shippingGuide->id,
      VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER],
      $shippingGuide->document_number
    );

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // VERIFICACIÓN (Comentada para implementar después)
    // Verificar si ya existe en la BD intermedia antes de enviar
    // $existingTransfer = DB::connection('dbtp')
    //     ->table('neInTbTransferenciaInventario')
    //     ->where('EmpresaId', Company::AP_DYNAMICS)
    //     ->where('TransferenciaId', $shippingGuide->document_number)
    //     ->first();
    //
    // if ($existingTransfer) {
    //     $transferLog->updateProcesoEstado(
    //         $existingTransfer->ProcesoEstado ?? 0,
    //         $existingTransfer->ProcesoError ?? null
    //     );
    //     return;
    // }

    try {
      // Sincronizar cabecera de transferencia
      $transferLog->markAsInProgress();
      $syncService->sync('inventory_transfer', $shippingGuide, 'create');
      $transferLog->updateProcesoEstado(0); // 0 = En proceso en la BD intermedia

    } catch (\Exception $e) {
      $transferLog->markAsFailed("Error al sincronizar transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transferencia de inventario
   */
  protected function syncInventoryTransferSerial(ShippingGuides $shippingGuide, DatabaseSyncService $syncService): void
  {
    $transferDetailLog = $this->getOrCreateLog(
      $shippingGuide->id,
      VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL],
      $shippingGuide->document_number
    );

    // Si ya está completado, no hacer nada
    if ($transferDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar datos para sincronización del detalle
      $detailData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => 'REP-' . $shippingGuide->correlative,
        'Linea' => 1,
        'Serie' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
        'ArticuloId' => $shippingGuide->vehicleMovement->vehicle->model->code ?? "N/A",
        'DatoUsuario1' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
        'DatoUsuario2' => $shippingGuide->vehicleMovement->vehicle->vin ?? "N/A",
      ];

      // Sincronizar detalle de transferencia
      $transferDetailLog->markAsInProgress();
      $syncService->sync('inventory_transfer_dts', $detailData, 'create');
      $transferDetailLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transferDetailLog->markAsFailed("Error al sincronizar detalle de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el serial (VIN) de transferencia de inventario
   */
  protected function syncInventoryTransferDetail(ShippingGuides $shippingGuide, DatabaseSyncService $syncService): void
  {
    $transferSerialLog = $this->getOrCreateLog(
      $shippingGuide->id,
      VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL],
      $shippingGuide->vehicleMovement?->vehicle?->vin
    );

    // Si ya está completado, no hacer nada
    if ($transferSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Preparar datos para sincronización del serial
      $serialData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $shippingGuide->document_number,
        'Linea' => 1,
        'ArticuloId' => $shippingGuide->vehicleMovement?->vehicle?->model->code ?? 'N/A',
        'Serie' => $shippingGuide->vehicleMovement?->vehicle?->vin,
        'Motivo' => '',
        'UnidadMedidaId' => 'UND',
        'Cantidad' => 1,
        'AlmacenId_Ini' => $shippingGuide->sedeTransmitter->warehouse_code,
        'AlmacenId_Fin' => $shippingGuide->sedeReceiver->warehouse_code,
      ];

      // Sincronizar serial de transferencia
      $transferSerialLog->markAsInProgress();
      $syncService->sync('inventory_transfer_dt', $serialData, 'create');
      $transferSerialLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transferSerialLog->markAsFailed("Error al sincronizar serial de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica si todos los pasos están completos
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
        'status_dynamic' => 'synced',
      ]);
      Log::info('Guía de remisión sincronizada completamente', ['id' => $shippingGuide->id]);
    } elseif ($hasFailed) {
      $shippingGuide->update([
        'status_dynamic' => 'sync_failed',
      ]);
      Log::warning('Falló la sincronización de guía de remisión', ['id' => $shippingGuide->id]);
    }
  }

  /**
   * Obtiene o crea un registro de log
   */
  protected function getOrCreateLog(int $shippingGuideId, string $step, string $tableName, ?string $externalId = null): VehiclePurchaseOrderMigrationLog
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
    Log::error('SyncShippingGuideJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
    ]);
  }
}
