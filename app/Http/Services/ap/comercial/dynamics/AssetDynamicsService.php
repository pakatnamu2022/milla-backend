<?php

namespace App\Http\Services\ap\comercial\dynamics;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ApAsset;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza a Dynamics (base intermedia dbtp) la transacción de inventario que saca
 * un vehículo VN de la cuenta de almacén (20) y lo lleva a la cuenta de activos (33).
 *
 * Escribe en neInTbTransaccionInventario / ...Det / ...DtS con TransaccionId = AC-00000042.
 * Sigue el mismo patrón de verificación (existe → lee ProcesoEstado; no existe → inserta)
 * que SaleShippingGuideDynamicsService e InternalNoteDynamicsService.
 */
class AssetDynamicsService
{
  public function __construct(
    protected DatabaseSyncService     $syncService,
    protected AssetMigrationLogService $logService
  ) {}

  public function verifyTransaction(ApAsset $asset): void
  {
    $log = $this->findLog($asset, VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION);
    if (!$log || $this->skip($log)) {
      return;
    }

    $transactionId = $this->logService->buildAssetTransactionId($asset);

    $existing = DB::connection('dbtp')
      ->table('neInTbTransaccionInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existing) {
      $this->syncTransaction($asset);
      return;
    }

    $log->updateProcesoEstado($existing->ProcesoEstado ?? 0, $existing->ProcesoError ?? null, true);
  }

  public function verifyTransactionDetail(ApAsset $asset): void
  {
    $log = $this->findLog($asset, VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_DETAIL);
    if (!$log || $this->skip($log)) {
      return;
    }

    $transactionId = $this->logService->buildAssetTransactionId($asset);

    $existing = DB::connection('dbtp')
      ->table('neInTbTransaccionInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existing) {
      $this->syncTransactionDetail($asset);
      return;
    }

    $log->updateProcesoEstado(1);
  }

  public function verifyTransactionSerial(ApAsset $asset): void
  {
    $log = $this->findLog($asset, VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_SERIAL);
    if (!$log || $this->skip($log)) {
      return;
    }

    $transactionId = $this->logService->buildAssetTransactionId($asset);
    $vin = $asset->vehicle?->vin;

    $existing = DB::connection('dbtp')
      ->table('neInTbTransaccionInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->where('Serie', $vin)
      ->first();

    if (!$existing) {
      $this->syncTransactionSerial($asset);
      return;
    }

    $log->updateProcesoEstado(1);
  }

  // ---------------------------------------------------------------------------

  protected function syncTransaction(ApAsset $asset): void
  {
    $log = $this->logService->getOrCreateLog(
      $asset,
      VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION],
      $this->logService->buildAssetTransactionId($asset)
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $transactionId = $this->logService->buildAssetTransactionId($asset);
      $date = ($asset->assigned_date ?? $asset->created_at)->format('Y-m-d');

      $data = [
        'EmpresaId'     => Company::AP_DYNAMICS,
        'TransaccionId' => $transactionId,
        'FechaEmision'  => $date,
        'FechaContable' => $date,
        'Procesar'      => 1,
        'ProcesoEstado' => 0,
        'ProcesoError'  => '',
        'FechaProceso'  => now()->format('Y-m-d H:i:s'),
      ];

      $log->markAsInProgress();
      $this->syncService->sync('inventory_transaction', $data, 'create');
      $log->updateProcesoEstado(0);

      if (empty($asset->dyn_series)) {
        $asset->update(['dyn_series' => $transactionId]);
      }
    } catch (Exception $e) {
      Log::error('Error al sincronizar transacción de activo', [
        'asset_id' => $asset->id,
        'error'    => $e->getMessage(),
      ]);
      $log->markAsFailed("Error al sincronizar transacción de activo: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function syncTransactionDetail(ApAsset $asset): void
  {
    $log = $this->logService->getOrCreateLog(
      $asset,
      VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_DETAIL],
      $this->logService->buildAssetTransactionId($asset)
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $data = $this->buildDetailData($asset);
      $log->markAsInProgress();
      $this->syncService->sync('inventory_transaction_dt', $data, 'create');
      $log->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transacción de activo', [
        'asset_id' => $asset->id,
        'error'    => $e->getMessage(),
      ]);
      $log->markAsFailed("Error al sincronizar detalle de transacción de activo: {$e->getMessage()}");
      throw $e;
    }
  }

  protected function syncTransactionSerial(ApAsset $asset): void
  {
    $log = $this->logService->getOrCreateLog(
      $asset,
      VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_SERIAL],
      $this->logService->buildAssetTransactionId($asset)
    );

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $vehicle = $asset->vehicle ?? throw new Exception('El activo no tiene vehículo asociado.');
      $transactionId = $this->logService->buildAssetTransactionId($asset);

      $data = [
        'EmpresaId'     => Company::AP_DYNAMICS,
        'TransaccionId' => $transactionId,
        'Linea'         => 1,
        'Serie'         => $vehicle->vin ?? 'N/A',
        'ArticuloId'    => $vehicle->model->code ?? 'N/A',
        'DatoUsuario1'  => $vehicle->vin ?? 'N/A',
        'DatoUsuario2'  => $vehicle->vin ?? 'N/A',
      ];

      $log->markAsInProgress();
      $this->syncService->sync('inventory_transaction_dts', $data, 'create');
      $log->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar serie de transacción de activo', [
        'asset_id' => $asset->id,
        'error'    => $e->getMessage(),
      ]);
      $log->markAsFailed("Error al sincronizar serie de transacción de activo: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Construye la línea de detalle: salida de la cuenta de inventario (20) del almacén VN
   * hacia la cuenta de activos (33), ambas con sufijo -{sede}.
   */
  protected function buildDetailData(ApAsset $asset): array
  {
    $vehicle = $asset->vehicle ?? throw new Exception('El activo no tiene vehículo asociado.');
    $warehouse = $vehicle->warehouse ?? throw new Exception('El vehículo no tiene almacén asignado.');
    $sede = $warehouse->sede ?? throw new Exception('El almacén del vehículo no tiene sede.');

    $sedeCode = $sede->dyn_code ?? throw new Exception('La sede del almacén no tiene código Dynamics.');

    $inventoryAccount = $warehouse->inventory_account
      ? $warehouse->inventory_account . '-' . $sedeCode
      : throw new Exception('El almacén no tiene configurada la Cuenta de Inventario.');

    $assetAccount = $warehouse->asset_account
      ? $warehouse->asset_account . '-' . $sedeCode
      : throw new Exception('El almacén no tiene configurada la Cuenta de Activos. Configúrela en Almacenes.');

    return [
      'EmpresaId'           => Company::AP_DYNAMICS,
      'TransaccionId'       => $this->logService->buildAssetTransactionId($asset),
      'Linea'               => 1,
      'ArticuloId'          => $vehicle->model->code ?? 'N/A',
      'Motivo'              => 'Conversion de vehiculo VN en activo fijo',
      'UnidadMedidaId'      => 'UND',
      'Cantidad'            => -1,
      'AlmacenId'           => $warehouse->dyn_code ?? '',
      'CostoUnitario'       => (float) ($vehicle->purchase_price ?? 0),
      'CuentaInventario'    => $inventoryAccount,
      'CuentaContrapartida' => $assetAccount,
    ];
  }

  protected function findLog(ApAsset $asset, string $step): ?VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::where('ap_vehicles_id', $asset->ap_vehicle_id)
      ->where('step', $step)
      ->first();
  }

  protected function skip(VehiclePurchaseOrderMigrationLog $log): bool
  {
    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return true;
    }

    if ($this->logService->hasExceededAttemptLimit($log)) {
      if ($log->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $log->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return true;
    }

    return false;
  }
}
