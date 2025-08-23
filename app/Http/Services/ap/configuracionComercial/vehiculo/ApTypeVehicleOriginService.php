<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApTypeVehicleOriginResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApTypeVehicleOrigin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApTypeVehicleOriginService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApTypeVehicleOrigin::class,
      $request,
      ApTypeVehicleOrigin::filters,
      ApTypeVehicleOrigin::sorts,
      ApTypeVehicleOriginResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApTypeVehicleOrigin::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de origen de vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApTypeVehicleOrigin::create($data);
    return new ApTypeVehicleOriginResource($engineType);
  }

  public function show($id)
  {
    return new ApTypeVehicleOriginResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApTypeVehicleOriginResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de origen de vehículo eliminado correctamente']);
  }
}
