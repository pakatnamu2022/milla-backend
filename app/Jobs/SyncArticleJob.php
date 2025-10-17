<?php

namespace App\Jobs;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncArticleJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $modelId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $model = ApModelsVn::find($this->modelId);

    if (!$model) {
      // Log::error("Model not found: {$this->modelId}");
      return;
    }

    try {
      // Buscar logs relacionados con este modelo
      $articleLogs = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
        ->where('external_id', $model->code)
        ->get();

      // Marcar como en progreso
      foreach ($articleLogs as $log) {
        $log->markAsInProgress();
      }

      // Sincronizar el artículo
      $resource = new ApModelsVnResource($model);
      $syncService->sync('article_model', $resource->toArray(request()), 'create');

      // Marcar como completado (con ProcesoEstado = 0, se actualizará después)
      foreach ($articleLogs as $log) {
        $log->updateProcesoEstado(0);
      }

      // Log::info("Article synced successfully for model: {$this->modelId}");
    } catch (\Exception $e) {
      // Log::error("Failed to sync article for model {$this->modelId}: {$e->getMessage()}");

      // Marcar logs como fallidos
      $articleLogs = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
        ->where('external_id', $model->code)
        ->get();

      foreach ($articleLogs as $log) {
        $log->markAsFailed($e->getMessage());
      }

      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    // Log::error("Failed SyncArticleJob for model {$this->modelId}: {$exception->getMessage()}");
  }
}
