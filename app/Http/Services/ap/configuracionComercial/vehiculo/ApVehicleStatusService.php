<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleStatusResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\configuracionComercial\venta\ApAssignSedePeriodo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApVehicleStatusService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleStatus::class,
      $request,
      ApVehicleStatus::filters,
      ApVehicleStatus::sorts,
      ApVehicleStatusResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApVehicleStatus::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Estado de vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApVehicleStatus::create($data);
    return new ApVehicleStatusResource($engineType);
  }

  public function show($id)
  {
    return new ApVehicleStatusResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApVehicleStatusResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Estado de vehículo eliminado correctamente']);
  }
}
