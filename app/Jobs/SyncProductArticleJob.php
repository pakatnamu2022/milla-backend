<?php

namespace App\Jobs;

use App\Http\Resources\ap\postventa\gestionProductos\ProductArticleResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductArticleJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $productId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    $product = Products::with(['brand', 'category', 'articleClass', 'unitMeasurement'])
      ->find($this->productId);

    if (!$product) {
      return;
    }

    if (!$product->dyn_code) {
      return;
    }

    try {
      // Buscar logs relacionados con este producto
      $articleLogs = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
        ->where('external_id', $product->dyn_code)
        ->get();

      // Marcar como en progreso
      foreach ($articleLogs as $log) {
        $log->markAsInProgress();
      }

      // Sincronizar el artículo
      $resource = new ProductArticleResource($product);
      $syncService->sync('article_product', $resource->toArray(request()), 'create');

      // Marcar como completado (con ProcesoEstado = 0, se actualizará después)
      foreach ($articleLogs as $log) {
        $log->updateProcesoEstado(0);
      }

    } catch (\Exception $e) {
      // Marcar logs como fallidos
      $articleLogs = VehiclePurchaseOrderMigrationLog::where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
        ->where('external_id', $product->dyn_code)
        ->get();

      foreach ($articleLogs as $log) {
        $log->markAsFailed($e->getMessage());
      }

      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("Failed SyncProductArticleJob for product {$this->productId}: {$exception->getMessage()}");
  }
}
