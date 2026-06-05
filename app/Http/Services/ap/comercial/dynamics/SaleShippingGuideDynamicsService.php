<?php

namespace App\Http\Services\ap\comercial\dynamics;

use App\Http\Resources\Dynamics\ShippingGuideDetailDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideHeaderDynamicsResource;
use App\Http\Resources\Dynamics\ShippingGuideSeriesDynamicsResource;
use App\Http\Services\DatabaseSyncService;
use App\Jobs\SyncAccountingEntryJob;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleShippingGuideDynamicsService
{
  public function __construct(
    protected DatabaseSyncService $syncService,
    protected ShippingGuideMigrationLogService $logService
  ) {}

  public function verifyTransaction(ShippingGuides $shippingGuide): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL,
      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_HEADER,
      VehiclePurchaseOrderMigrationLog::STEP_ACCOUNTING_ENTRY_DETAIL,
    ];

    foreach ($steps as $step) {
      $transactionLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$transactionLog) {
        continue;
      }

      if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      if (empty($shippingGuide->dyn_series)) {
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncTransaction($shippingGuide, $isCancelled);
        continue;
      }

      $transactionId = $this->logService->buildSaleTransactionId($shippingGuide, $step);

      $existingTransaction = DB::connection('dbtp')
        ->table('neInTbTransaccionInventario')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      if (!$existingTransaction) {
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncTransaction($shippingGuide, $isCancelled);
        continue;
      }

      $transactionLog->updateProcesoEstado(
        $existingTransaction->ProcesoEstado ?? 0,
        $existingTransaction->ProcesoError ?? null
      );

      if ($existingTransaction->ProcesoEstado == 1 && !str_contains($step, 'REVERSAL')) {
        SyncAccountingEntryJob::dispatch($shippingGuide->id)
          ->onQueue('sync')
          ->delay(now()->addSeconds(5));
      }
    }
  }

  public function verifyTransactionDetail(ShippingGuides $shippingGuide): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL,
    ];

    foreach ($steps as $step) {
      $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$detailLog) {
        continue;
      }

      if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      $transactionId = $this->logService->buildSaleTransactionId($shippingGuide, $step);

      $existingDetail = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      if (!$existingDetail) {
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncTransactionDetail($shippingGuide, $isCancelled);
        continue;
      }

      $detailLog->updateProcesoEstado(1);
    }
  }

  public function verifyTransactionSerial(ShippingGuides $shippingGuide): void
  {
    $steps = [
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL,
    ];

    foreach ($steps as $step) {
      $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
        ->where('step', $step)
        ->first();

      if (!$serialLog) {
        continue;
      }

      if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      $transactionId = $this->logService->buildSaleTransactionId($shippingGuide, $step);

      $existingSerial = DB::connection('dbtp')
        ->table('neInTbTransaccionInventarioDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
        ->first();

      if (!$existingSerial) {
        $isCancelled = str_contains($step, 'REVERSAL');
        $this->syncTransactionSerial($shippingGuide, $isCancelled);
        continue;
      }

      $serialLog->updateProcesoEstado(1);
    }
  }

  protected function syncTransaction(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE;

    $transactionLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      if (empty($shippingGuide->dyn_series)) {
        $prefix = $shippingGuide->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA ? 'CV-' : 'CS-';
        $transactionId = $prefix . $shippingGuide->document_number;
        $shippingGuide->update(['dyn_series' => $transactionId]);
      }

      $resource = new ShippingGuideHeaderDynamicsResource($shippingGuide);
      $data = $resource->toArray(request());
      $transactionLog->markAsInProgress();
      $this->syncService->sync('inventory_transaction', $data, 'create');
      $transactionLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('=== ERROR syncTransaction ===', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transactionLog->markAsFailed("Error al sincronizar transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function syncTransactionDetail(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    if (!$vehicle_vn_id) {
      throw new Exception("El vehículo asociado a la guía de remisión no tiene un ID válido.");
    }

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL;

    $transactionDetailLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_DETAIL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transactionDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $vehicleVn = Vehicles::findOrFail($vehicle_vn_id);
      $resource = new ShippingGuideDetailDynamicsResource($vehicleVn, $shippingGuide);
      $data = $resource->toArray(request());
      $transactionDetailLog->markAsInProgress();
      $this->syncService->sync('inventory_transaction_dt', $data, 'create');
      $transactionDetailLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transacción de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transactionDetailLog->markAsFailed("Error al sincronizar detalle de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function syncTransactionSerial(ShippingGuides $shippingGuide, bool $isCancelled): void
  {
    $vehicle_vn_id = $shippingGuide->vehicleMovement?->vehicle?->id ?? null;

    $step = $isCancelled
      ? VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL;

    $transactionSerialLog = $this->logService->getOrCreateLog(
      $shippingGuide->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SALE_SHIPPING_GUIDE_SERIAL],
      $shippingGuide->document_number,
      $vehicle_vn_id
    );

    if ($transactionSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $resource = new ShippingGuideSeriesDynamicsResource($shippingGuide);
      $data = $resource->toArray(request());
      $transactionSerialLog->markAsInProgress();
      $this->syncService->sync('inventory_transaction_dts', $data, 'create');
      $transactionSerialLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar serial de transacción de inventario', [
        'shipping_guide_id' => $shippingGuide->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transactionSerialLog->markAsFailed("Error al sincronizar serial de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }
}
