<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\IndexProductsRequest;
use App\Http\Requests\ap\postventa\gestionProductos\StoreProductsRequest;
use App\Http\Requests\ap\postventa\gestionProductos\UpdateProductsRequest;
use App\Http\Services\ap\postventa\gestionProductos\ProductsService;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
  protected ProductsService $service;

  public function __construct(ProductsService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of products with filters
   */
  public function index(IndexProductsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created product
   */
  public function store(StoreProductsRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified product
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified product
   */
  public function update(UpdateProductsRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified product
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get products with low stock
   */
  public function lowStock()
  {
    try {
      return $this->success($this->service->getLowStockProducts());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update product stock
   */
  public function updateStock(Request $request, $id)
  {
    try {
      $request->validate([
        'quantity' => 'required|numeric|min:0',
        'operation' => 'required|in:add,subtract,set',
      ]);

      return $this->success($this->service->updateStock(
        $id,
        $request->quantity,
        $request->operation
      ));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Get featured products
   */
  public function featured()
  {
    try {
      return $this->success($this->service->getFeaturedProducts());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Assign product to warehouse with zero stock
   */
  public function assignToWarehouse(Request $request)
  {
    try {
      $request->validate([
        'product_id' => 'required|exists:products,id',
        'warehouse_id' => 'required|exists:warehouse,id',
      ]);

      return $this->success($this->service->assignToWarehouse($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
