<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\UnitMeasurementResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UnitMeasurementService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      UnitMeasurement::class,
      $request,
      UnitMeasurement::filters,
      UnitMeasurement::sorts,
      UnitMeasurementResource::class,
    );
  }

  public function find($id)
  {
    $UnitMeasurement = UnitMeasurement::where('id', $id)->first();
    if (!$UnitMeasurement) {
      throw new Exception('Unidad de medida no encontrado');
    }
    return $UnitMeasurement;
  }

  public function store(Mixed $data)
  {
    $UnitMeasurement = UnitMeasurement::create($data);
    return new UnitMeasurementResource($UnitMeasurement);
  }

  public function show($id)
  {
    return new UnitMeasurementResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $UnitMeasurement = $this->find($data['id']);
    $UnitMeasurement->update($data);
    return new UnitMeasurementResource($UnitMeasurement);
  }

  public function destroy($id)
  {
    $UnitMeasurement = $this->find($id);
    DB::transaction(function () use ($UnitMeasurement) {
      $UnitMeasurement->delete();
    });
    return response()->json(['message' => 'Unidad de medida eliminado correctamente']);
  }
}
