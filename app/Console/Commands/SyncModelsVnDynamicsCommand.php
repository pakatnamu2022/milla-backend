<?php

namespace App\Console\Commands;

use App\Jobs\SyncModelVnJob;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVnSyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

    $log = ApModelsVnSyncLog::create([
      'model_vn_id' => $model->id,
      'code'        => $model->code,
      'status'      => ApModelsVnSyncLog::STATUS_PENDING,
      'attempts'    => 0,
    ]);
    SyncModelVnJob::dispatch($model->id, $log->id);

    $this->info("Job despachado para modelo #{$modelId} ({$model->code}) — Log #{$log->id}");
    return Command::SUCCESS;
  }

  protected function syncAll(bool $retryFailed): int
  {
    if ($retryFailed) {
      // Crear nuevos logs para los que fallaron y despachar jobs
      $failedLogs = ApModelsVnSyncLog::where('status', ApModelsVnSyncLog::STATUS_FAILED)
        ->with('model')
        ->get();

      if ($failedLogs->isEmpty()) {
        $this->info('No hay modelos VN fallidos para reintentar.');
        return Command::SUCCESS;
      }

      $this->info("Re-encolando {$failedLogs->count()} modelos fallidos...");
      foreach ($failedLogs as $log) {
        if (!$log->model) continue;
        $newLog = ApModelsVnSyncLog::create([
          'model_vn_id' => $log->model_vn_id,
          'code'        => $log->code,
          'status'      => ApModelsVnSyncLog::STATUS_PENDING,
          'attempts'    => 0,
        ]);
        SyncModelVnJob::dispatch($log->model_vn_id, $newLog->id);
      }

      $this->info("Jobs re-encolados exitosamente.");
      return Command::SUCCESS;
    }

    // Modo scheduler: solo actúa sobre logs ya creados (vía endpoint individual o syncAll).
    // No crea nuevos logs. Completed se ignora (ya terminó).
    $dispatched = 0;

    // 1. pending > 30s sin que el worker lo tome → re-despachar
    $pendingLogs = ApModelsVnSyncLog::where('status', ApModelsVnSyncLog::STATUS_PENDING)
      ->where('created_at', '<=', now()->subSeconds(30))
      ->get();

    foreach ($pendingLogs as $log) {
      SyncModelVnJob::dispatch($log->model_vn_id, $log->id);
      $dispatched++;
    }

    // 2. in_progress > 5 min (worker caído) → resetear a pending y re-despachar
    $stuckLogs = ApModelsVnSyncLog::where('status', ApModelsVnSyncLog::STATUS_IN_PROGRESS)
      ->where('last_attempt_at', '<=', now()->subMinutes(5))
      ->get();

    foreach ($stuckLogs as $log) {
      $log->update(['status' => ApModelsVnSyncLog::STATUS_PENDING]);
      SyncModelVnJob::dispatch($log->model_vn_id, $log->id);
      $dispatched++;
    }

    // 3. failed con menos de 5 intentos → reintentar automáticamente
    $failedLogs = ApModelsVnSyncLog::where('status', ApModelsVnSyncLog::STATUS_FAILED)
      ->where('attempts', '<', 5)
      ->get();

    foreach ($failedLogs as $log) {
      $log->update(['status' => ApModelsVnSyncLog::STATUS_PENDING]);
      SyncModelVnJob::dispatch($log->model_vn_id, $log->id);
      $dispatched++;
    }

    if ($dispatched === 0) {
      $this->info('No hay modelos VN pendientes de sincronizar.');
      return Command::SUCCESS;
    }

    Log::channel('daily')->info('[ap:sync-models-vn] Jobs despachados', ['count' => $dispatched]);
    $this->info("Jobs despachados: {$dispatched}.");
    return Command::SUCCESS;
  }
}
