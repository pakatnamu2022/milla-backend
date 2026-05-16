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

  public function update(Request $request, $id)
  {
    try {
      //validamos el $request
      $data = $request->validate([
        'warehouse_id' => 'required|integer|exists:warehouse,id',
        'product_id' => 'required|integer|exists:products,id',
        'minimum_stock' => 'required|integer|min:0',
        'maximum_stock' => 'required|integer|min:0',
      ]);

      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

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

  public function exportInventory(Request $request)
  {
    try {
      // Validate request
      $request->validate([
        'warehouse_id' => 'required|integer|exists:warehouse,id',
        'stock_type' => 'nullable|string|in:all,with_stock,without_stock',
        'title' => 'nullable|string',
      ]);

      return $this->service->exportInventory($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function compareStockWithDynamics(Request $request): JsonResponse
  {
    try {
      // Validate request
      $request->validate([
        'warehouse_id' => 'required|integer|exists:warehouse,id',
      ]);

      $warehouseId = $request->input('warehouse_id');
      $result = $this->service->compareStockWithDynamics($warehouseId);

      return response()->json([
        'success' => true,
        'data' => $result,
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
