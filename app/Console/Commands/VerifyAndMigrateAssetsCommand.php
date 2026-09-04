<?php

namespace App\Console\Commands;

use App\Jobs\VerifyAndMigrateAssetJob;
use App\Models\ap\comercial\ApAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyAndMigrateAssetsCommand extends Command
{
  protected $signature = 'assets:verify-migration {--all : Procesar todos los activos pendientes}';

  protected $description = 'Verifica y migra a Dynamics las transacciones de activos (vehículo VN → activo fijo). Omite los que alcanzaron el límite de intentos.';

  public function handle(): int
  {
    $assets = ApAsset::whereIn('migration_status', [
      ApAsset::MIGRATION_STATUS_PENDING,
      ApAsset::MIGRATION_STATUS_IN_PROGRESS,
    ])->whereNull('deleted_at')->get();

    if ($assets->isEmpty()) {
      $this->info('No hay activos pendientes de migración');
      return self::SUCCESS;
    }

    $logService = app(\App\Http\Services\ap\comercial\dynamics\AssetMigrationLogService::class);

    $processed = 0;
    $skipped = 0;

    foreach ($assets as $asset) {
      if ($logService->hasMaxAttemptsReached($asset)) {
        $skipped++;
        $this->warn("Activo ID {$asset->id} omitido: alcanzó el límite de intentos");
        continue;
      }

      try {
        VerifyAndMigrateAssetJob::dispatch($asset->id);
        $processed++;
      } catch (\Exception $e) {
        $this->error("Error despachando job para activo ID {$asset->id}: {$e->getMessage()}");
        Log::error('Error despachando VerifyAndMigrateAssetJob', [
          'asset_id' => $asset->id,
          'error'    => $e->getMessage(),
        ]);
      }
    }

    $this->info("Procesamiento completado: {$processed} jobs despachados, {$skipped} omitidos");

    return self::SUCCESS;
  }
}
