<?php

namespace App\Http\Services\ap\comercial\dynamics;

use App\Models\ap\comercial\ApAsset;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;

/**
 * Gestiona los logs de migración para la conversión de un vehículo VN en activo fijo.
 * Sigue el mismo patrón que InternalNoteMigrationLogService pero identificando el log
 * por el vehículo (ap_vehicles_id) ya que un vehículo tiene a lo sumo un activo.
 */
class AssetMigrationLogService
{
  public function hasExceededAttemptLimit(VehiclePurchaseOrderMigrationLog $log): bool
  {
    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      return $log->attempts >= VehiclePurchaseOrderMigrationLog::MAX_PENDING_ATTEMPTS;
    }

    if ($log->status === VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS) {
      return $log->attempts >= VehiclePurchaseOrderMigrationLog::MAX_IN_PROGRESS_ATTEMPTS;
    }

    return false;
  }

  /**
   * Formato del TransaccionId enviado a Dynamics: AC-00042 (id del activo con padding).
   */
  public function buildAssetTransactionId(ApAsset $asset): string
  {
    return 'AC-' . str_pad((string) $asset->id, 8, '0', STR_PAD_LEFT);
  }

  const STEPS = [
    VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION,
    VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_DETAIL,
    VehiclePurchaseOrderMigrationLog::STEP_ASSET_TRANSACTION_SERIAL,
  ];

  public function ensureAssetLogsExist(ApAsset $asset): void
  {
    $externalId = $this->buildAssetTransactionId($asset);

    foreach (self::STEPS as $step) {
      $this->getOrCreateLog(
        $asset,
        $step,
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[$step],
        $externalId
      );
    }
  }

  public function getOrCreateLog(
    ApAsset $asset,
    string  $step,
    string  $tableName,
    string  $externalId
  ): VehiclePurchaseOrderMigrationLog {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'ap_vehicles_id' => $asset->ap_vehicle_id,
        'step'           => $step,
      ],
      [
        'table_name'     => $tableName,
        'external_id'    => $externalId,
        'status'         => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'proceso_estado' => 0,
        'attempts'       => 0,
      ]
    );
  }

  public function checkAndUpdateCompletionStatus(ApAsset $asset): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('ap_vehicles_id', $asset->ap_vehicle_id)
      ->whereIn('step', self::STEPS)
      ->get();

    if ($logs->isEmpty()) {
      return;
    }

    $allCompleted = $logs->every(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);
    $anyFailed = $logs->contains(fn($log) => $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED);

    if ($allCompleted) {
      $asset->update(['migration_status' => ApAsset::MIGRATION_STATUS_COMPLETED]);
    } elseif ($anyFailed) {
      $asset->update(['migration_status' => ApAsset::MIGRATION_STATUS_FAILED]);
    } else {
      $asset->update(['migration_status' => ApAsset::MIGRATION_STATUS_IN_PROGRESS]);
    }
  }

  public function hasMaxAttemptsReached(ApAsset $asset): bool
  {
    $maxAttempts = VehiclePurchaseOrderMigrationLog::where('ap_vehicles_id', $asset->ap_vehicle_id)
      ->whereIn('step', self::STEPS)
      ->max('attempts');

    return $maxAttempts !== null && $maxAttempts >= VehiclePurchaseOrderMigrationLog::MAX_PENDING_ATTEMPTS;
  }
}
