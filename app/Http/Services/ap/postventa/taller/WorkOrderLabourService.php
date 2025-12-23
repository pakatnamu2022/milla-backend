<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderLabourResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkOrderLabourService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      WorkOrderLabour::class,
      $request,
      WorkOrderLabour::filters,
      WorkOrderLabour::sorts,
      WorkOrderLabourResource::class,
    );
  }

  public function find($id)
  {
    $workOrderLabour = WorkOrderLabour::with(['worker', 'workOrder'])->where('id', $id)->first();
    if (!$workOrderLabour) {
      throw new Exception('Mano de obra no encontrada');
    }
    return $workOrderLabour;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Calcular el costo total automáticamente
      if (isset($data['time_spent']) && isset($data['hourly_rate'])) {
        $data['total_cost'] = $data['time_spent'] * $data['hourly_rate'];
      }

      if (auth()->check()) {
        $data['worker_id'] = auth()->user()->person->id;
      }

      $workOrderLabour = WorkOrderLabour::create($data);
      return new WorkOrderLabourResource($workOrderLabour->load(['worker', 'workOrder']));
    });
  }

  public function show($id)
  {
    return new WorkOrderLabourResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $workOrderLabour = $this->find($data['id']);

      if (auth()->check()) {
        $data['worker_id'] = auth()->user()->person->id;
      }

      // Calcular el costo total automáticamente si se actualizan time_spent u hourly_rate
      if (isset($data['time_spent']) || isset($data['hourly_rate'])) {
        $timeSpent = $data['time_spent'] ?? $workOrderLabour->time_spent;
        $hourlyRate = $data['hourly_rate'] ?? $workOrderLabour->hourly_rate;
        $data['total_cost'] = $timeSpent * $hourlyRate;
      }

      $workOrderLabour->update($data);
      return new WorkOrderLabourResource($workOrderLabour->load(['worker', 'workOrder']));
    });
  }

  public function destroy($id)
  {
    $workOrderLabour = $this->find($id);
    DB::transaction(function () use ($workOrderLabour) {
      $workOrderLabour->delete();
    });
    return response()->json(['message' => 'Mano de obra eliminada correctamente']);
  }
}
