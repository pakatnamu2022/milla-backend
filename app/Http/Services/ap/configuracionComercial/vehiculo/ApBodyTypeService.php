<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApBodyTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApBodyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApBodyTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApBodyType::class,
      $request,
      ApBodyType::filters,
      ApBodyType::sorts,
      ApBodyTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApBodyType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de carrocería no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApBodyType::create($data);
    return new ApBodyTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApBodyTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApBodyTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de carrocería eliminado correctamente']);
  }
}
