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

  public function getWarehouseStockWithTransit(Request $request)
  {
    try {
      $request->validate([
        'warehouse_id' => 'required|integer|exists:warehouse,id',
      ]);

      $stocks = $this->service->getWarehouseStockWithTransit($request);

      return ProductWarehouseStockResource::collection($stocks);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
