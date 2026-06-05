<?php

namespace App\Http\Services\ap\comercial\dynamics;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\comercial\VehicleMovementService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferShippingGuideDynamicsService
{
  public function __construct(
    protected DatabaseSyncService $syncService,
    protected ShippingGuideMigrationLogService $logService
  ) {}

  public function verifyTransfer(ShippingGuides $shippingGuide): void
  {
    $transferLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER)
      ->first();

    if (!$transferLog) {
      return;
    }

    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if (empty($shippingGuide->dyn_series)) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncTransfer($shippingGuide, $isCancelled);
      return;
    }

    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingTransfer) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncTransfer($shippingGuide, $isCancelled);
      return;
    }

    $transferLog->updateProcesoEstado(
      $existingTransfer->ProcesoEstado ?? 0,
      $existingTransfer->ProcesoError ?? null
    );

    if ($transferLog->proceso_estado === 1) {
      $vehicle = $shippingGuide->vehicleMovement?->vehicle;
      if (!$vehicle) {
        throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido. ShippingGuide ID: {$shippingGuide->id}");
      }

      $vehicleMovementService = new VehicleMovementService();
      if ($shippingGuide->document_type === ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA) {
        // Traslado interno: el vehículo ya está en inventario, solo cambia de sede/almacén
        $vehicleMovementService->storeInternalTransferCompletedVehicleMovement($vehicle, $shippingGuide);
      } else {
        // Compra u otro flujo: el vehículo entra a inventario por primera vez
        $vehicleMovementService->storeInventoryVehicleMovement($vehicle->id);
      }
    }
  }

  public function verifyTransferDetail(ShippingGuides $shippingGuide): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL)
      ->first();

    if (!$detailLog) {
      return;
    }

    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Guard para evitar query con TransferenciaId = null
    if (empty($shippingGuide->dyn_series)) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncTransferDetail($shippingGuide, $isCancelled);
      return;
    }

    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->first();

    if (!$existingDetail) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncTransferDetail($shippingGuide, $isCancelled);
      return;
    }

    $detailLog->updateProcesoEstado(1);
  }

  public function verifyTransferSerial(ShippingGuides $shippingGuide): void
  {
    $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL)
      ->first();

    if (!$serialLog) {
      return;
    }

    if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    $existingSerial = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', $shippingGuide->dyn_series)
      ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
      ->first();

    if (!$existingSerial) {
      $isCancelled = $shippingGuide->status === false || $shippingGuide->cancelled_at !== null;
      $this->syncTransferSerial($shippingGuide, $isCancelled);
      return;
    }

    $procesoEstado = $existingSerial->ProcesoEstado ?? 0;
    $serialLog->updateProcesoEstado(1);

    if ($procesoEstado === "1") {
      $this->updateVehicleWarehouse($shippingGuide);
    }
  }

  protected function syncTransfer(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;
    $prefix = $shippingGuide->getTransferPrefix($shippingGuide);

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER;

    $transferLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $transferId = $prefix . $shippingGuide->document_number;
      if ($isCancelled) {
        $transferId .= '*';
      }

      $data = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'FechaEmision' => ($shippingGuide->dynamics_date ?? $shippingGuide->issue_date)?->format('Y-m-d') ?? throw new Exception("La fecha de emisión es obligatoria."),
        'FechaContable' => ($shippingGuide->dynamics_date ?? $shippingGuide->issue_date)?->format('Y-m-d') ?? throw new Exception("La fecha contable es obligatoria."),
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => '',
        'FechaProceso' => now()->format('Y-m-d H:i:s'),
      ];

      $transferLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer', $data, 'create');
      $transferLog->updateProcesoEstado(0);

      $shippingGuide->update(['dyn_series' => $transferId]);
    } catch (Exception $e) {
      Log::error('Error al sincronizar transferencia de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transferLog->markAsFailed("Error al sincronizar transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  // Nota: los nombres syncTransferDetail / syncTransferSerial están invertidos internamente
  // respecto a los steps que usan (SERIAL vs DETAIL). Se mantiene el comportamiento original.

  protected function syncTransferDetail(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    if (!$vehicle_vn_id) {
      throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
    }

    $prefix = $shippingGuide->getTransferPrefix($shippingGuide);
    $transferIdOriginal = $prefix . $shippingGuide->document_number;
    $transferIdFormatted = $transferIdOriginal;

    if ($isCancelled) {
      $transferIdFormatted .= '*';
    }

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL;

    $transferSerialLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transferSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $vehicleVn = Vehicles::findOrFail(
        $shippingGuide->vehicleMovement?->vehicle?->id
        ?? throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.")
      );

      $type_operation_id = $vehicleVn->type_operation_id ?? null;
      $class_id = $vehicleVn->model->class_id ?? null;

      if ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_COMPRA) {
        $sede_id = $shippingGuide->sedeReceiver->id ?? null;

        $baseQuery = Warehouse::where('sede_id', $sede_id)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('status', true);

        $warehouseStart = (clone $baseQuery)->where('is_received', false);
        $warehouseEnd = (clone $baseQuery)->where('is_received', true);

        $warehouseStartCode = $warehouseStart->value('dyn_code');
        $warehouseEndCode = $warehouseEnd->value('dyn_code');

        if ($isCancelled) {
          [$warehouseStartCode, $warehouseEndCode] = [$warehouseEndCode, $warehouseStartCode];
        }

        $sede = Sede::findOrFail($sede_id)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $inventoryAccount = $warehouseStart->value('inventory_account')
          ? $warehouseStart->value('inventory_account') . '-' . $sede
          : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $warehouseEnd->value('inventory_account')
          ? $warehouseEnd->value('inventory_account') . '-' . $sede
          : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          [$inventoryAccount, $counterpartInventoryAccount] = [$counterpartInventoryAccount, $inventoryAccount];
        }

      } elseif ($shippingGuide->document_type === ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA) {
        $sedeTransmitterId = $shippingGuide->sedeTransmitter->id ?? null;
        $sedeReceiverId = $shippingGuide->sedeReceiver->id ?? null;

        $transmitterQuery = Warehouse::where('sede_id', $sedeTransmitterId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', false)
          ->where('status', true);

        $receiverQuery = Warehouse::where('sede_id', $sedeReceiverId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', false)
          ->where('status', true);

        $sedeStart = Sede::findOrFail($sedeTransmitterId)->dyn_code ?? throw new Exception('La Sede transmisora no fue encontrada.');
        $sedeEnd = Sede::findOrFail($sedeReceiverId)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $warehouseStartCode = $transmitterQuery->value('dyn_code');
        $warehouseEndCode = $receiverQuery->value('dyn_code');

        if ($isCancelled) {
          [$warehouseStartCode, $warehouseEndCode] = [$warehouseEndCode, $warehouseStartCode];
        }

        $inventoryAccount = $transmitterQuery->value('inventory_account')
          ? $transmitterQuery->value('inventory_account') . '-' . $sedeStart
          : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $receiverQuery->value('inventory_account')
          ? $receiverQuery->value('inventory_account') . '-' . $sedeEnd
          : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          [$inventoryAccount, $counterpartInventoryAccount] = [$counterpartInventoryAccount, $inventoryAccount];
        }

      } elseif ($shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_TRASLADO_SEDE) {
        $sedeTransmitterId = $shippingGuide->sedeTransmitter->id ?? null;
        $sedeReceiverId = $shippingGuide->sedeReceiver->id ?? null;

        $transmitterQuery = Warehouse::where('sede_id', $sedeTransmitterId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', true)
          ->where('status', true);

        $receiverQuery = Warehouse::where('sede_id', $sedeReceiverId)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('is_received', true)
          ->where('status', true);

        $sedeStart = Sede::findOrFail($sedeTransmitterId)->dyn_code ?? throw new Exception('La Sede transmisora no fue encontrada.');
        $sedeEnd = Sede::findOrFail($sedeReceiverId)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $warehouseStartCode = $transmitterQuery->value('dyn_code');
        $warehouseEndCode = $receiverQuery->value('dyn_code');

        if ($isCancelled) {
          [$warehouseStartCode, $warehouseEndCode] = [$warehouseEndCode, $warehouseStartCode];
        }

        $inventoryAccount = $transmitterQuery->value('inventory_account')
          ? $transmitterQuery->value('inventory_account') . '-' . $sedeStart
          : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = $receiverQuery->value('inventory_account')
          ? $receiverQuery->value('inventory_account') . '-' . $sedeEnd
          : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          [$inventoryAccount, $counterpartInventoryAccount] = [$counterpartInventoryAccount, $inventoryAccount];
        }

      } else {
        $sede_id = $shippingGuide->sedeReceiver->id ?? null;

        $baseQuery = Warehouse::where('sede_id', $sede_id)
          ->where('type_operation_id', $type_operation_id)
          ->where('article_class_id', $class_id)
          ->where('status', true);

        $warehouseStartCode = (clone $baseQuery)->where('is_received', true)->value('dyn_code');
        $warehouseEndCode = (clone $baseQuery)->where('is_received', false)->value('dyn_code');

        if ($isCancelled) {
          [$warehouseStartCode, $warehouseEndCode] = [$warehouseEndCode, $warehouseStartCode];
        }

        $sede = Sede::findOrFail($sede_id)->dyn_code ?? throw new Exception('La Sede receptora no fue encontrada.');

        $inventoryAccount = (clone $baseQuery)->where('is_received', true)->value('inventory_account')
          ? (clone $baseQuery)->where('is_received', true)->value('inventory_account') . '-' . $sede
          : throw new Exception('La Cuenta de Inventario no fue encontrada.');
        $counterpartInventoryAccount = (clone $baseQuery)->where('is_received', false)->value('inventory_account')
          ? (clone $baseQuery)->where('is_received', false)->value('inventory_account') . '-' . $sede
          : throw new Exception('La Cuenta Contrapartida no fue encontrada.');

        if ($isCancelled) {
          [$inventoryAccount, $counterpartInventoryAccount] = [$counterpartInventoryAccount, $inventoryAccount];
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

      $transferSerialLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer_dt', $serialData, 'create');
      $transferSerialLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transferencia de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transferSerialLog->markAsFailed("Error al sincronizar serial de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function syncTransferSerial(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;
    $prefix = $shippingGuide->getTransferPrefix($shippingGuide);

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL;

    $transferDetailLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transferDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $transferId = $prefix . $shippingGuide->document_number;
      if ($isCancelled) {
        $transferId .= '*';
      }

      $detailData = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransferenciaId' => $transferId,
        'Linea' => 1,
        'Serie' => $shippingGuide->vehicleMovement->vehicle->vin ?? 'N/A',
        'ArticuloId' => $shippingGuide->vehicleMovement->vehicle->model->code ?? 'N/A',
        'DatoUsuario1' => $shippingGuide->vehicleMovement->vehicle->vin ?? 'N/A',
        'DatoUsuario2' => $shippingGuide->vehicleMovement->vehicle->vin ?? 'N/A',
      ];

      $transferDetailLog->markAsInProgress();
      $this->syncService->sync('inventory_transfer_dts', $detailData, 'create');
      $transferDetailLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transferencia de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transferDetailLog->markAsFailed("Error al sincronizar detalle de transferencia: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function updateVehicleWarehouse(ShippingGuides $shippingGuide): void
  {
    try {
      $vehicle = $shippingGuide->vehicleMovement?->vehicle;

      if (!$vehicle) {
        return;
      }

      // GUIA_INTERNA transfiere entre existencias (is_received = false);
      // otros flujos (TRASLADO_SEDE, COMPRA) terminan en almacén de recepción (is_received = true).
      $isReceived = $shippingGuide->document_type !== ShippingGuides::DOCUMENT_TYPE_GUIA_INTERNA;

      $warehouseId = Warehouse::where('sede_id', $shippingGuide->sedeReceiver->id)
        ->where('type_operation_id', $vehicle->type_operation_id)
        ->where('article_class_id', $vehicle->model->class_id)
        ->where('is_received', $isReceived)
        ->value('id');

      if ($warehouseId) {
        $vehicle->update(['warehouse_id' => $warehouseId]);
      }
    } catch (Exception $e) {
      Log::error('Error al actualizar warehouse_id del vehículo', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
      ]);
    }
  }
}
