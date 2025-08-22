<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFuelTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApFuelTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApFuelType::class,
      $request,
      ApFuelType::filters,
      ApFuelType::sorts,
      ApFuelTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApFuelType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de combustible no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApFuelType::create($data);
    return new ApFuelTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApFuelTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApFuelTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de combustible eliminado correctamente']);
  }
}
