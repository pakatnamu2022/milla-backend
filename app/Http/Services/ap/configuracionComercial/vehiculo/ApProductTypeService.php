<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApProductTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApProductTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApProductType::class,
      $request,
      ApProductType::filters,
      ApProductType::sorts,
      ApProductTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApProductType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de producto vehículo de marcha no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApProductType::create($data);
    return new ApProductTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApProductTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApProductTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de producto vehículo de marcha eliminado correctamente']);
  }
}
