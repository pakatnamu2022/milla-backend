<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApVehicleTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleType::class,
      $request,
      ApVehicleType::filters,
      ApVehicleType::sorts,
      ApVehicleTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApVehicleType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApVehicleType::create($data);
    return new ApVehicleTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApVehicleTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApVehicleTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de vehículo eliminado correctamente']);
  }
}
