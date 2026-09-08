<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Exports\ap\postventa\ProductShelfExport;
use App\Http\Resources\ap\postventa\gestionProductos\ProductShelfResource;
use App\Http\Resources\ap\postventa\gestionProductos\ProductWarehouseShelfResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\gestionProductos\ProductShelf;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseShelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProductShelfService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ProductShelf::class,
      $request,
      ProductShelf::filters,
      ProductShelf::sorts,
      ProductShelfResource::class,
      ['warehouse', 'creator']
    );
  }

  public function find($id)
  {
    $shelf = ProductShelf::with(['warehouse', 'creator'])->where('id', $id)->first();
    if (!$shelf) {
      throw new Exception('Estante no encontrado');
    }
    return $shelf;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      $data['code'] = $this->generateShelfCode($data['warehouse_id']);
      $data['created_by'] = Auth::id();

      $shelf = ProductShelf::create($data);

      DB::commit();
      return new ProductShelfResource($shelf->load(['warehouse', 'creator']));
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  public function show($id)
  {
    return new ProductShelfResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $shelf = $this->find($data['id']);

      if (isset($data['warehouse_id']) && $data['warehouse_id'] != $shelf->warehouse_id) {
        $productsCount = ProductWarehouseShelf::where('product_shelf_id', $shelf->id)->count();
        if ($productsCount > 0) {
          throw new Exception('No se puede cambiar el almacén de un estante que tiene productos asignados');
        }
        $data['code'] = $this->generateShelfCode($data['warehouse_id']);
      }

      $shelf->update($data);

      DB::commit();
      return new ProductShelfResource($shelf->load(['warehouse', 'creator']));
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $shelf = $this->find($id);

      $productsCount = ProductWarehouseShelf::where('product_shelf_id', $shelf->id)->count();
      if ($productsCount > 0) {
        throw new Exception('No se puede eliminar un estante que tiene productos asignados');
      }

      $shelf->delete();

      DB::commit();
      return response()->json(['message' => 'Estante eliminado correctamente']);
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  private function generateShelfCode($warehouseId): string
  {
    $lastShelf = ProductShelf::where('warehouse_id', $warehouseId)
      ->orderBy('code', 'desc')
      ->first();

    if (!$lastShelf) {
      return 'EST-001';
    }

    $lastNumber = (int)substr($lastShelf->code, 4);
    $newNumber = $lastNumber + 1;

    return 'EST-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
  }

  public function assignProducts(mixed $data)
  {
    DB::beginTransaction();
    try {
      $shelf = $this->find($data['product_shelf_id']);

      foreach ($data['products'] as $productData) {
        $productStock = ProductWarehouseStock::findOrFail($productData['product_warehouse_stock_id']);

        if ($productStock->warehouse_id != $shelf->warehouse_id) {
          throw new Exception('El producto debe pertenecer al mismo almacén que el estante');
        }

        $exists = ProductWarehouseShelf::where('product_warehouse_stock_id', $productData['product_warehouse_stock_id'])
          ->where('product_shelf_id', $data['product_shelf_id'])
          ->exists();

        if ($exists) {
          continue;
        }

        ProductWarehouseShelf::create([
          'product_warehouse_stock_id' => $productData['product_warehouse_stock_id'],
          'product_shelf_id' => $data['product_shelf_id'],
          'position' => $productData['position'] ?? null,
        ]);
      }

      DB::commit();
      return response()->json(['message' => 'Productos asignados correctamente']);
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  public function removeProduct(mixed $data)
  {
    DB::beginTransaction();
    try {
      $assignment = ProductWarehouseShelf::where('product_warehouse_stock_id', $data['product_warehouse_stock_id'])
        ->where('product_shelf_id', $data['product_shelf_id'])
        ->first();

      if (!$assignment) {
        throw new Exception('La asignación no existe');
      }

      $assignment->delete();

      DB::commit();
      return response()->json(['message' => 'Producto removido del estante correctamente']);
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  public function getShelfProducts($shelfId)
  {
    $shelf = $this->find($shelfId);

    $products = ProductWarehouseShelf::where('product_shelf_id', $shelfId)
      ->with(['productWarehouseStock.product', 'productWarehouseStock.warehouse'])
      ->get();

    return ProductWarehouseShelfResource::collection($products);
  }

  public function updatePosition(mixed $data)
  {
    DB::beginTransaction();
    try {
      $assignment = ProductWarehouseShelf::where('product_warehouse_stock_id', $data['product_warehouse_stock_id'])
        ->where('product_shelf_id', $data['product_shelf_id'])
        ->first();

      if (!$assignment) {
        throw new Exception('La asignación del producto al estante no existe');
      }

      $assignment->position = $data['position'] ?? null;
      $assignment->save();

      DB::commit();
      return response()->json([
        'message' => 'Posición actualizada correctamente',
        'data' => new ProductWarehouseShelfResource($assignment->load(['productWarehouseStock.product', 'productWarehouseStock.warehouse']))
      ]);
    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  public function exportShelfProducts($shelfId)
  {
    try {
      $shelf = $this->find($shelfId);

      // Obtener productos del estante con todas las relaciones necesarias
      $shelfProducts = ProductWarehouseShelf::where('product_shelf_id', $shelfId)
        ->with([
          'productWarehouseStock.product.category',
          'productWarehouseStock.product.brand',
          'productWarehouseStock.product.unitMeasurement',
        ])
        ->orderByRaw('CASE WHEN position IS NULL THEN 1 ELSE 0 END, position ASC')
        ->get();

      // Mapear los datos para el export
      $data = $shelfProducts->map(function ($item) use ($shelf) {
        $product = $item->productWarehouseStock->product;

        return [
          'shelf_code' => $shelf->code ?? 'N/A',
          'shelf_label' => $shelf->label ?? 'N/A',
          'position' => $item->position ?? '-',
          'product_code' => $product->code ?? 'N/A',
          'product_dyn_code' => $product->dyn_code ?? 'N/A',
          'product_name' => $product->name ?? 'N/A',
          'category' => $product->category->description ?? 'N/A',
          'brand' => $product->brand->name ?? 'N/A',
          'unit_measurement' => $product->unitMeasurement->dyn_code ?? 'N/A',
        ];
      });

      $filename = 'Productos_Estante_' . $shelf->code . '_' . date('Y-m-d_His') . '.xlsx';
      $title = 'Productos - Estante ' . $shelf->label;

      return Excel::download(new ProductShelfExport($data, $title), $filename);
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
