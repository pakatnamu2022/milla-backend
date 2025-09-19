<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApSafeCreditGoalResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\venta\ApSafeCreditGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApSafeCreditGoalService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApSafeCreditGoal::class,
      $request,
      ApSafeCreditGoal::filters,
      ApSafeCreditGoal::sorts,
      ApSafeCreditGoalResource::class,
    );
  }

  public function find($id)
  {
    $ApSafeCreditGoal = ApSafeCreditGoal::where('id', $id)->first();
    if (!$ApSafeCreditGoal) {
      throw new Exception('Registro no encontrado');
    }
    return $ApSafeCreditGoal;
  }

  public function store(mixed $data)
  {
    $existingRecord = ApSafeCreditGoal::where('year', $data['year'])
      ->where('month', $data['month'])
      ->where('sede_id', $data['sede_id'])
      ->where('type', $data['type'])
      ->whereNull('deleted_at')
      ->first();
    
    if ($existingRecord) {
      throw new Exception('Ya existe un registro para el mismo año, mes, sede y tipo');
    }
    $ApSafeCreditGoal = ApSafeCreditGoal::create($data);
    return new ApSafeCreditGoalResource($ApSafeCreditGoal);
  }

  public function show($id)
  {
    return new ApSafeCreditGoalResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $ApSafeCreditGoal = $this->find($data['id']);
    $ApSafeCreditGoal->update($data);
    return new ApSafeCreditGoalResource($ApSafeCreditGoal);
  }

  public function destroy($id)
  {
    $ApSafeCreditGoal = $this->find($id);
    DB::transaction(function () use ($ApSafeCreditGoal) {
      $ApSafeCreditGoal->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }
}
