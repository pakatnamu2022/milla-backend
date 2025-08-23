<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleColorResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApVehicleColorService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleColor::class,
      $request,
      ApVehicleColor::filters,
      ApVehicleColor::sorts,
      ApVehicleColorResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApVehicleColor::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Color de vehiculo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApVehicleColor::create($data);
    return new ApVehicleColorResource($engineType);
  }

  public function show($id)
  {
    return new ApVehicleColorResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApVehicleColorResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Color de vehiculo eliminado correctamente']);
  }
}
