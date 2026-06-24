<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RecalculateProductCostJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2;
  public int $timeout = 600; // 10 minutos para procesar historiales grandes
  public int $backoff = 120;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int     $productId,
    public int     $warehouseId,
    public ?string $fromDate = null
  )
  {
    $this->onQueue('product_cost_recalculation');
  }

  /**
   * Execute the job.
   */
  public function handle(ProductWarehouseStockService $stockService): void
  {
    try {
      // Llamar al método centralizado de recálculo
      $stockService->rebuildWeightedAverageCostHistory(
        $this->productId,
        $this->warehouseId,
        $this->fromDate
      );
    } catch (\Exception $e) {
      Log::error('Error en RecalculateProductCostJob', [
        'product_id' => $this->productId,
        'warehouse_id' => $this->warehouseId,
        'from_date' => $this->fromDate,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      throw $e;
    }
  }

  /**
   * Maneja el fallo del job
   */
  public function failed(\Throwable $exception): void
  {
    Log::error('RecalculateProductCostJob failed', [
      'product_id' => $this->productId,
      'warehouse_id' => $this->warehouseId,
      'from_date' => $this->fromDate,
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString(),
    ]);
  }
}
