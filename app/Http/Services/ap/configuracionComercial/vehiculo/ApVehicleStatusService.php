<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleStatusResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
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
    $vehicleStatus = ApVehicleStatus::where('id', $id)->first();
    if (!$vehicleStatus) {
      throw new Exception('Estado de vehículo no encontrado');
    }
    return $vehicleStatus;
  }

  public function store(array $data)
  {
    $vehicleStatus = ApVehicleStatus::create($data);
    return new ApVehicleStatusResource($vehicleStatus);
  }

  public function show($id)
  {
    return new ApVehicleStatusResource($this->find($id));
  }

  public function update($data)
  {
    $vehicleStatus = $this->find($data['id']);
    $vehicleStatus->update($data);
    return new ApVehicleStatusResource($vehicleStatus);
  }

  public function destroy($id)
  {
    $vehicleStatus = $this->find($id);
    DB::transaction(function () use ($vehicleStatus) {
      $vehicleStatus->delete();
    });
    return response()->json(['message' => 'Estado de vehículo eliminado correctamente']);
  }
}
