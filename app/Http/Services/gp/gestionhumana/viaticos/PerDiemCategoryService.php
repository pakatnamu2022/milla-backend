<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemCategoryResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerDiemCategoryService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemCategory::class,
      $request,
      PerDiemCategory::filters,
      PerDiemCategory::sorts,
      PerDiemCategoryResource::class,
    );
  }

  public function find($id)
  {
    $perDiemCategory = PerDiemCategory::where('id', $id)->first();
    if (!$perDiemCategory) {
      throw new Exception('Categoría de viático no encontrada');
    }
    return $perDiemCategory;
  }

  public function store(mixed $data)
  {
    $perDiemCategory = PerDiemCategory::create($data);
    return new PerDiemCategoryResource($perDiemCategory);
  }

  public function show($id)
  {
    return new PerDiemCategoryResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $perDiemCategory = $this->find($data['id']);
    $perDiemCategory->update($data);
    return new PerDiemCategoryResource($perDiemCategory);
  }

  public function destroy($id)
  {
    $perDiemCategory = $this->find($id);
    DB::transaction(function () use ($perDiemCategory) {
      $perDiemCategory->delete();
    });
    return response()->json(['message' => 'Categoría de viático eliminada correctamente']);
  }
}
