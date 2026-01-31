<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
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
    $query->with(['category', 'brand', 'unitMeasurement', 'articleClass']);

    // Add total stock calculation if needed
    if ($request->has('with_total_stock') && $request->with_total_stock) {
      $query->withTotalStock();
    }

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
    $product = Products::with(['category', 'brand', 'unitMeasurement', 'articleClass', 'warehouseStocks.warehouse'])
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

      // Generate correlative and append to code
      $correlative = Products::generateNextCorrelative();
      $data['dyn_code'] = str_replace('X', '', $data['dyn_code']);
      $data['dyn_code'] = $data['dyn_code'] . '-' . str_pad($correlative, 6, '0', STR_PAD_LEFT);

      $product = Products::create($data);

      // NEW: Create warehouse stock records if provided
      if (isset($data['warehouses']) && is_array($data['warehouses'])) {
        foreach ($data['warehouses'] as $warehouseData) {
          ProductWarehouseStock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseData['warehouse_id'],
            'quantity' => $warehouseData['initial_quantity'] ?? 0,
            'available_quantity' => $warehouseData['initial_quantity'] ?? 0,
            'minimum_stock' => $warehouseData['minimum_stock'] ?? 0,
            'maximum_stock' => $warehouseData['maximum_stock'] ?? null,
          ]);
        }
      } // DEPRECATED: Backwards compatibility - if warehouse_id is provided
      elseif (isset($data['warehouse_id']) && $data['warehouse_id']) {
        ProductWarehouseStock::create([
          'product_id' => $product->id,
          'warehouse_id' => $data['warehouse_id'],
          'quantity' => $data['current_stock'] ?? 0,
          'available_quantity' => $data['current_stock'] ?? 0,
          'minimum_stock' => $data['minimum_stock'] ?? 0,
          'maximum_stock' => $data['maximum_stock'] ?? null,
        ]);
      }

      DB::commit();
      return new ProductsResource($product->load(['category', 'brand', 'unitMeasurement', 'warehouseStocks.warehouse']));
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

      // Verificar si el producto tiene una orden de compra/factura registrada
      if ($product->hasPurchaseOrder()) {
        throw new Exception(
          'No se puede editar el producto porque tiene una factura a proveedor registrada.'
        );
      }

      // If dyn_code is being updated, append the correlative
      if (isset($data['dyn_code']) && $data['dyn_code'] !== $product->code) {
        // Remove 'X' placeholders
        $data['dyn_code'] = str_replace('X', '', $data['dyn_code']);
        // Remove existing correlative if present
        $dynCodeWithoutCorrelative = preg_replace('/-\d+$/', '', $data['dyn_code']);

        // Use the product's ID as correlative to maintain uniqueness
        $correlative = $product->id;
        $data['dyn_code'] = $dynCodeWithoutCorrelative . '-' . str_pad($correlative, 6, '0', STR_PAD_LEFT);
      }

      $product->update($data);

      DB::commit();
      return new ProductsResource($product->load(['category', 'brand', 'unitMeasurement', 'warehouseStocks.warehouse']));
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

      // Verificar si el producto tiene movimientos de inventario
      $movementDetails = InventoryMovementDetail::where('product_id', $id)
        ->with(['movement'])
        ->get();

      if ($movementDetails->isNotEmpty()) {
        $movementCount = $movementDetails->count();
        throw new Exception(
          "No se puede eliminar el producto porque tiene {$movementCount} movimiento(s) de inventario asociado(s). " .
          "Debe eliminar primero todos los movimientos de inventario relacionados con este producto."
        );
      }

      // Verificar si el producto tiene stock en algún almacén
      $warehousesWithStock = ProductWarehouseStock::where('product_id', $id)
        ->where(function ($query) {
          $query->where('quantity', '>', 0)
            ->orWhere('available_quantity', '>', 0);
        })
        ->with('warehouse')
        ->get();

      if ($warehousesWithStock->isNotEmpty()) {
        // Construir mensaje con los almacenes que tienen stock
        $warehouseNames = $warehousesWithStock->map(function ($stock) {
          return $stock->warehouse->name . ' (Stock: ' . $stock->quantity . ', Disponible: ' . $stock->available_quantity . ')';
        })->implode(', ');

        throw new Exception(
          'No se puede eliminar el producto porque tiene stock en los siguientes almacenes: ' . $warehouseNames .
          '. Debe vaciar primero el stock de todos los almacenes.'
        );
      }

      // Si no hay stock, eliminar los registros de product_warehouse_stock
      ProductWarehouseStock::where('product_id', $id)->delete();

      // Eliminar el producto
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
      ->with(['category', 'brand', 'unitMeasurement', 'articleClass', 'warehouseStocks.warehouse'])
      ->get();

    return ProductsResource::collection($products);
  }

  /**
   * DEPRECATED: Update stock for a product
   *
   * This method is deprecated and maintained only for backwards compatibility.
   * Stock updates should now be done through InventoryMovement to maintain proper audit trail.
   *
   * @deprecated Use InventoryMovementService instead
   */
  public function updateStock($productId, $quantity, $operation = 'add')
  {
    throw new Exception(
      'Este método está deprecado. ' .
      'Los cambios de stock deben realizarse a través de Movimientos de Inventario (InventoryMovement) ' .
      'para mantener la trazabilidad completa. ' .
      'Por favor, utilice el módulo de Ajustes de Inventario.'
    );

    // DEPRECATED CODE - Kept for reference but not executed
    /*
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
    */
  }

  /**
   * Get featured products
   */
  public function getFeaturedProducts()
  {
    $products = Products::featured()
      ->active()
      ->with(['category', 'brand', 'unitMeasurement', 'articleClass', 'warehouseStocks.warehouse'])
      ->get();

    return ProductsResource::collection($products);
  }

  /**
   * Assign product to warehouse with zero stock
   * This is useful when a product was created without warehouse assignment
   * and needs to be added to a warehouse later
   */
  public function assignToWarehouse(Mixed $data)
  {
    DB::beginTransaction();
    try {
      $product = $this->find($data['product_id']);

      // Check if product is already in this warehouse
      $existingStock = ProductWarehouseStock::where('product_id', $data['product_id'])
        ->where('warehouse_id', $data['warehouse_id'])
        ->first();

      if ($existingStock) {
        throw new Exception('El producto ya está asignado a este almacén');
      }

      // Create warehouse stock record with zero stock
      $warehouseStock = ProductWarehouseStock::create([
        'product_id' => $data['product_id'],
        'warehouse_id' => $data['warehouse_id'],
        'quantity' => 0,
        'quantity_in_transit' => 0,
        'quantity_pending_credit_note' => 0,
        'reserved_quantity' => 0,
        'available_quantity' => 0,
        'minimum_stock' => 0,
        'maximum_stock' => 0,
      ]);

      DB::commit();
      return [
        'message' => 'Producto asignado al almacén exitosamente',
        'warehouse_stock' => $warehouseStock->load('warehouse')
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
