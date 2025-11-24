<?php

namespace App\Jobs;

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
use Illuminate\Support\Facades\Log;
use Throwable;

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

    // 1. Sincronizar transferencia de inventario (cabecera)
    $this->syncInventoryTransfer($shippingGuide, $syncService, $isCancelled);

    // 2. Sincronizar detalle de transferencia
    $this->syncInventoryTransferDetail($shippingGuide, $syncService, $isCancelled);

    // 3. Sincronizar serial de transferencia (VIN)
    $this->syncInventoryTransferSerial($shippingGuide, $syncService, $isCancelled);

    // Nota: La verificación de completitud se hace en VerifyAndMigrateShippingGuideJob
    // que se ejecuta periódicamente cada 30 segundos
  }

  /**
   * Sincroniza la cabecera de transferencia de inventario
   * @throws Throwable
   */
  protected function syncInventoryTransfer(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;
    $prefix = $this->getTransferPrefix($shippingGuide);

    $vehicle = $shippingGuide->vehicleMovement?->vehicle;

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
      if (!$vehicle) {
        throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
      }

      $vehicleMovementService = new VehicleMovementService();
      $vehicleMovementService->storeInventoryVehicleMovement($vehicle);
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
      $syncService->sync('inventory_transfer', $data, 'create');
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
  protected function syncInventoryTransferSerial(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
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
  protected function syncInventoryTransferDetail(ShippingGuides $shippingGuide, DatabaseSyncService $syncService, bool $isCancelled): void
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
      $syncService->sync('inventory_transfer_dt', $serialData, 'create');
      $transferSerialLog->updateProcesoEstado(0);

    } catch (\Exception $e) {
      $transferSerialLog->markAsFailed("Error al sincronizar serial de transferencia: {$e->getMessage()}");
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
   * Obtiene el prefijo del TransferenciaId según el motivo de traslado
   */
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
