<?php

namespace App\Jobs;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnDynamicsResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVnSyncLog;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncModelVnJob implements ShouldQueue
{
  use Queueable;

  public int $tries   = 3;
  public int $timeout = 60;

  public function __construct(
    public int $modelId,
    public int $logId
  ) {
    $this->onQueue('models_vn_sync');
  }

  public function handle(DatabaseSyncService $syncService): void
  {
    $log = ApModelsVnSyncLog::find($this->logId);
    if (!$log) return;

    $model = ApModelsVn::with(['classArticle', 'family.brand'])->find($this->modelId);
    if (!$model) {
      $log->markAsFailed("Modelo ID {$this->modelId} no encontrado.");
      return;
    }

    // Guard: skip if another worker already picked this log up
    if ($log->status !== ApModelsVnSyncLog::STATUS_PENDING) return;

    $log->markAsInProgress();

    try {
      $alreadyExists = DB::connection('dbtp')
        ->table('neInTbArticulo')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Articulo', $model->code)
        ->exists();

      if ($alreadyExists) {
        $log->markAsCompleted(['already_exists' => true, 'Articulo' => $model->code]);
        return;
      }

      // Validate and build the Dynamics payload for storage
      $dynamicsPayload = (new ApModelsVnDynamicsResource($model))->toArray(request());

      // Sync using the standard resource (maps to article_model config keys)
      $syncService->sync('article_model', (new ApModelsVnResource($model))->toArray(request()), 'create');

      $log->markAsCompleted($dynamicsPayload);
    } catch (\Throwable $e) {
      $log->markAsFailed($e->getMessage());
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    $log = ApModelsVnSyncLog::find($this->logId);
    $log?->markAsFailed($exception->getMessage());
  }
}
