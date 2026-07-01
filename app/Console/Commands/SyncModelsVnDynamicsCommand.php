<?php

namespace App\Console\Commands;

use App\Jobs\SyncModelVnJob;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVnSyncLog;
use Illuminate\Console\Command;

class SyncModelsVnDynamicsCommand extends Command
{
  protected $signature = 'ap:sync-models-vn
                          {--model= : ID del modelo específico a sincronizar}
                          {--retry-failed : Re-encolar modelos con estado failed}';

  protected $description = 'Sincroniza modelos VN (artículos) hacia Dynamics (neInTbArticulo)';

  public function handle(): int
  {
    $modelId     = $this->option('model');
    $retryFailed = $this->option('retry-failed');

    if ($modelId) {
      return $this->syncSingle((int) $modelId);
    }

    return $this->syncAll($retryFailed);
  }

  protected function syncSingle(int $modelId): int
  {
    $model = ApModelsVn::with(['classArticle', 'family.brand'])->find($modelId);

    if (!$model) {
      $this->error("Modelo #{$modelId} no encontrado.");
      return Command::FAILURE;
    }

    if (!$model->code) {
      $this->error("El modelo #{$modelId} no tiene código asignado, no se puede sincronizar.");
      return Command::FAILURE;
    }

    $log = $this->createLog($model);
    SyncModelVnJob::dispatch($model->id, $log->id);

    $this->info("Job despachado para modelo #{$modelId} ({$model->code}) — Log #{$log->id}");
    return Command::SUCCESS;
  }

  protected function syncAll(bool $retryFailed): int
  {
    $query = ApModelsVn::whereNotNull('code');

    if ($retryFailed) {
      // Re-encolar solo los que fallaron
      $syncedIds = ApModelsVnSyncLog::where('status', ApModelsVnSyncLog::STATUS_FAILED)
        ->pluck('model_vn_id');
      $query->whereIn('id', $syncedIds);
    } else {
      // Solo los que nunca se sincronizaron o están pendientes
      $syncedIds = ApModelsVnSyncLog::whereIn('status', [
        ApModelsVnSyncLog::STATUS_COMPLETED,
        ApModelsVnSyncLog::STATUS_IN_PROGRESS,
        ApModelsVnSyncLog::STATUS_PENDING,
      ])->pluck('model_vn_id');
      $query->whereNotIn('id', $syncedIds);
    }

    $models = $query->with(['classArticle', 'family.brand'])->orderBy('id')->get();

    if ($models->isEmpty()) {
      $this->info('No hay modelos VN pendientes de sincronizar.');
      return Command::SUCCESS;
    }

    $label = $retryFailed ? 'fallidos' : 'pendientes';
    $this->info("Despachando jobs para {$models->count()} modelos {$label}...");

    $bar = $this->output->createProgressBar($models->count());
    $bar->start();

    foreach ($models as $model) {
      $log = $this->createLog($model);
      SyncModelVnJob::dispatch($model->id, $log->id);
      $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    $this->info("Jobs despachados exitosamente.");
    return Command::SUCCESS;
  }

  protected function createLog(ApModelsVn $model): ApModelsVnSyncLog
  {
    return ApModelsVnSyncLog::create([
      'model_vn_id' => $model->id,
      'code'        => $model->code,
      'status'      => ApModelsVnSyncLog::STATUS_PENDING,
      'attempts'    => 0,
    ]);
  }
}
