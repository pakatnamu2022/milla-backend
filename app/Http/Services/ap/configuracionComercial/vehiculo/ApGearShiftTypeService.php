<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApGearShiftTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApGearShiftType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApGearShiftTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApGearShiftType::class,
      $request,
      ApGearShiftType::filters,
      ApGearShiftType::sorts,
      ApGearShiftTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApGearShiftType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de cambio de marcha no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApGearShiftType::create($data);
    return new ApGearShiftTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApGearShiftTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApGearShiftTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de cambio de marcha eliminado correctamente']);
  }
}
