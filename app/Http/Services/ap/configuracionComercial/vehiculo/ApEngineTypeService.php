<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApEngineTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApEngineType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApEngineTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApEngineType::class,
      $request,
      ApEngineType::filters,
      ApEngineType::sorts,
      ApEngineTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApEngineType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de Motor no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApEngineType::create($data);
    return new ApEngineTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApEngineTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApEngineTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de Motor eliminado correctamente']);
  }
}
