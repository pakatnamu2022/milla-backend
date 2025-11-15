<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\gestionProductos\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = Products::query();

    // Apply special filters
    if ($request->has('low_stock') && $request->low_stock) {
      $query->lowStock();
    }

    if ($request->has('out_of_stock') && $request->out_of_stock) {
      $query->outOfStock();
    }

    // Apply eager loading for relationships
    $query->with(['category', 'brand', 'unitMeasurement', 'warehouse']);

    return $this->getFilteredResults(
      Products::class,
      $request,
      Products::filters,
      Products::sorts,
      ProductsResource::class,
      $query
    );
  }

  public function find($id)
  {
    $product = Products::with(['category', 'brand', 'unitMeasurement', 'warehouse'])
      ->where('id', $id)
      ->first();

    if (!$product) {
      throw new Exception('Producto no encontrado');
    }

    return $product;
  }

  public function store(Mixed $data)
  {
    DB::beginTransaction();
    try {
      // Set default values if not provided
      if (!isset($data['tax_rate'])) {
        $data['tax_rate'] = 18.00; // Default IGV for Peru
      }

      if (!isset($data['is_taxable'])) {
        $data['is_taxable'] = true;
      }

      if (!isset($data['product_type'])) {
        $data['product_type'] = 'GOOD';
      }

      if (!isset($data['status'])) {
        $data['status'] = 'ACTIVE';
      }

      if (!isset($data['current_stock'])) {
        $data['current_stock'] = 0;
      }

      if (!isset($data['minimum_stock'])) {
        $data['minimum_stock'] = 0;
      }

      $product = Products::create($data);

      DB::commit();
      return new ProductsResource($product->load(['category', 'brand', 'unitMeasurement', 'warehouse']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ProductsResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    DB::beginTransaction();
    try {
      $product = $this->find($data['id']);
      $product->update($data);

      DB::commit();
      return new ProductsResource($product->load(['category', 'brand', 'unitMeasurement', 'warehouse']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $product = $this->find($id);

      // Check if product can be deleted (add business logic here)
      // For example, check if product has associated orders, inventory movements, etc.

      $product->delete();

      DB::commit();
      return response()->json(['message' => 'Producto eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get products with low stock
   */
  public function getLowStockProducts()
  {
    $products = Products::lowStock()
      ->with(['category', 'brand', 'unitMeasurement', 'warehouse'])
      ->get();

    return ProductsResource::collection($products);
  }

  /**
   * Update stock for a product
   */
  public function updateStock($productId, $quantity, $operation = 'add')
  {
    DB::beginTransaction();
    try {
      $product = $this->find($productId);

      if ($operation === 'add') {
        $product->current_stock += $quantity;
      } elseif ($operation === 'subtract') {
        if ($product->current_stock < $quantity) {
          throw new Exception('Stock insuficiente para realizar la operación');
        }
        $product->current_stock -= $quantity;
      } elseif ($operation === 'set') {
        $product->current_stock = $quantity;
      }

      $product->save();

      DB::commit();
      return new ProductsResource($product->load(['category', 'brand', 'unitMeasurement', 'warehouse']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get featured products
   */
  public function getFeaturedProducts()
  {
    $products = Products::featured()
      ->active()
      ->with(['category', 'brand', 'unitMeasurement', 'warehouse'])
      ->get();

    return ProductsResource::collection($products);
  }
}
