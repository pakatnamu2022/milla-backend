<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Resources\ap\postventa\gestionProductos\ProductWarehouseStockResource;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductWarehouseStockController extends Controller
{
  protected ProductWarehouseStockService $service;

  public function __construct(ProductWarehouseStockService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get stock by multiple product IDs
   * Returns stock information for multiple products across all warehouses
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function getStockByProductIds(Request $request): JsonResponse
  {
    try {
      // Validate request
      $request->validate([
        'product_ids' => 'required|array',
        'product_ids.*' => 'required|integer|exists:products,id',
      ]);

      $productIds = $request->input('product_ids');
      $result = $this->service->getStockByProductIds($productIds);

      return response()->json([
        'success' => true,
        'data' => $result,
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
