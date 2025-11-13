<?php

namespace App\Http\Services\ap\postventa\gestionProductos;

use App\Http\Resources\ap\postventa\gestionProductos\ProductCategoryResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\gestionProductos\ProductCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCategoryService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ProductCategory::class,
      $request,
      ProductCategory::filters,
      ProductCategory::sorts,
      ProductCategoryResource::class,
    );
  }

  public function find($id)
  {
    $productCategory = ProductCategory::where('id', $id)->first();
    if (!$productCategory) {
      throw new Exception('Categoría no encontrado');
    }
    return $productCategory;
  }

  public function store(Mixed $data)
  {
    $productCategory = ProductCategory::create($data);
    return new ProductCategoryResource($productCategory);
  }

  public function show($id)
  {
    return new ProductCategoryResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $productCategory = $this->find($data['id']);
    $productCategory->update($data);
    return new ProductCategoryResource($productCategory);
  }

  public function destroy($id)
  {
    $productCategory = $this->find($id);
    DB::transaction(function () use ($productCategory) {
      $productCategory->delete();
    });
    return response()->json(['message' => 'Categoría eliminado correctamente']);
  }
}
