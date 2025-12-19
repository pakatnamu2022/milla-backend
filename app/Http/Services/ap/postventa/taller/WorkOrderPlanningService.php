<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class WorkOrderPlanningService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApWorkOrderPlanning::class,
      $request,
      ApWorkOrderPlanning::filters,
      ApWorkOrderPlanning::sorts,
      WorkOrderPlanningResource::class,
      ['worker', 'workOrder', 'sessions']
    );
  }

  public function find($id)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder', 'sessions'])
      ->where('id', $id)
      ->first();

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    return $planning;
  }

  public function store(mixed $data)
  {
    // Si no se proporciona planned_end_datetime pero sí estimated_hours, calcularlo
    if (!isset($data['planned_end_datetime']) && isset($data['estimated_hours'])) {
      $startDatetime = $data['planned_start_datetime'];
      $estimatedHours = floatval($data['estimated_hours']);

      // Convertir horas a minutos para mayor precisión
      $minutes = $estimatedHours * 60;

      // Calcular la fecha/hora de fin sumando las horas estimadas
      $data['planned_end_datetime'] = Carbon::parse($startDatetime)->addMinutes($minutes);
    }

    $planning = ApWorkOrderPlanning::create($data);
    return new WorkOrderPlanningResource($planning->load(['worker', 'workOrder']));
  }

  public function show($id)
  {
    return new WorkOrderPlanningResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $planning = $this->find($data['id']);
    $planning->update($data);
    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  public function destroy($id)
  {
    $planning = $this->find($id);
    $planning->delete();
    return response()->json(['message' => 'Planificación eliminada correctamente']);
  }
}
