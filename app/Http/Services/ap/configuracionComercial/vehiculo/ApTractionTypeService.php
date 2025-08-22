<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApTractionTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApTractionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApTractionTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApTractionType::class,
      $request,
      ApTractionType::filters,
      ApTractionType::sorts,
      ApTractionTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApTractionType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de tracción no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApTractionType::create($data);
    return new ApTractionTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApTractionTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApTractionTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de tracción eliminado correctamente']);
  }
}
