<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\comercial\dynamics\AssetDynamicsService;
use App\Http\Services\ap\comercial\dynamics\AssetMigrationLogService;
use App\Models\ap\comercial\ApAsset;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Migra a Dynamics 365 (base intermedia dbtp) la transacción de inventario que convierte
 * un vehículo VN (INVENTARIO_VN) en activo fijo: salida de la cuenta de almacén (20) hacia
 * la cuenta de activos (33).
 *
 * Puede despacharse con un $assetId concreto o sin id para procesar todos los activos
 * con migration_status = pending | in_progress (EXCLUYE failed y completed).
 */
class VerifyAndMigrateAssetJob implements ShouldQueue
{
  use Queueable;

  const QUEUE_DEFAULT = 'assets';

  public int $tries = 2;
  public int $timeout = 300;
  public int $backoff = 120;

  public function __construct(
    public ?int $assetId = null,
    string      $queue = self::QUEUE_DEFAULT
  ) {
    $this->onQueue($queue);
  }

  public function handle(
    DatabaseSyncService       $syncService,
    AssetMigrationLogService  $logService
  ): void {
    $dynamicsService = new AssetDynamicsService($syncService, $logService);

    try {
      if ($this->assetId) {
        $this->processAsset($this->assetId, $logService, $dynamicsService);
        return;
      }

      ApAsset::whereIn('migration_status', [
        ApAsset::MIGRATION_STATUS_PENDING,
        ApAsset::MIGRATION_STATUS_IN_PROGRESS,
      ])->whereNull('deleted_at')->pluck('id')->each(function ($id) use ($logService, $dynamicsService) {
        try {
          $this->processAsset($id, $logService, $dynamicsService);
        } catch (Exception $e) {
          Log::error('Error procesando activo', ['asset_id' => $id, 'error' => $e->getMessage()]);
        }
      });
    } catch (Exception $e) {
      Log::error('Error en VerifyAndMigrateAssetJob', [
        'asset_id' => $this->assetId,
        'error'    => $e->getMessage(),
      ]);

      if ($this->assetId) {
        $asset = ApAsset::find($this->assetId);
        if ($asset) {
          $logService->checkAndUpdateCompletionStatus($asset);
        }
      }

      throw $e;
    }
  }

  protected function processAsset(
    int                      $assetId,
    AssetMigrationLogService  $logService,
    AssetDynamicsService      $dynamicsService
  ): void {
    $asset = ApAsset::with(['vehicle.warehouse.sede', 'vehicle.model'])->find($assetId);

    if (!$asset) {
      return;
    }

    if ($asset->migration_status === ApAsset::MIGRATION_STATUS_PENDING) {
      $asset->update(['migration_status' => ApAsset::MIGRATION_STATUS_IN_PROGRESS]);
    }

    $logService->ensureAssetLogsExist($asset);

    $dynamicsService->verifyTransaction($asset);
    $dynamicsService->verifyTransactionDetail($asset);
    $dynamicsService->verifyTransactionSerial($asset);

    $logService->checkAndUpdateCompletionStatus($asset);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateAssetJob falló completamente', [
      'asset_id' => $this->assetId,
      'error'    => $exception->getMessage(),
    ]);

    if ($this->assetId) {
      ApAsset::where('id', $this->assetId)
        ->update(['migration_status' => ApAsset::MIGRATION_STATUS_FAILED]);
    }
  }
}
