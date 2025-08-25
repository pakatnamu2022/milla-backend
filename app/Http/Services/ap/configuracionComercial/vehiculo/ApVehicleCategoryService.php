<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleCategoryResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApVehicleCategoryService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleCategory::class,
      $request,
      ApVehicleCategory::filters,
      ApVehicleCategory::sorts,
      ApVehicleCategoryResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApVehicleCategory::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Categoría de Vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApVehicleCategory::create($data);
    return new ApVehicleCategoryResource($engineType);
  }

  public function show($id)
  {
    return new ApVehicleCategoryResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApVehicleCategoryResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Categoría de Vehículo eliminado correctamente']);
  }
}
