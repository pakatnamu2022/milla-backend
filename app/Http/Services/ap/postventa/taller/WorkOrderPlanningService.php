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
    // Establecer valor por defecto para type si no se envía
    $data['type'] = $data['type'] ?? 'internal';

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

  public function consolidated($workOrderId)
  {
    // Obtener todos los registros de planificación para la orden de trabajo
    $plannings = ApWorkOrderPlanning::with(['worker'])
      ->where('work_order_id', $workOrderId)
      ->get();

    if ($plannings->isEmpty()) {
      return [];
    }

    // Agrupar por group_number y description
    $grouped = $plannings->groupBy(function ($item) {
      return $item->group_number . '|' . $item->description;
    });

    $consolidated = [];

    foreach ($grouped as $key => $items) {
      [$groupNumber, $description] = explode('|', $key);

      // Calcular totales
      $totalEstimatedHours = $items->sum('estimated_hours');
      $totalActualHours = $items->sum('actual_hours');

      // Calcular porcentaje de progreso
      $progressPercentage = $totalEstimatedHours > 0
        ? round(($totalActualHours / $totalEstimatedHours) * 100, 2)
        : 0;

      // Determinar estado general del grupo
      $statuses = $items->pluck('status')->unique();
      $groupStatus = $this->determineGroupStatus($statuses);

      // Obtener información de trabajadores
      $workers = $items->map(function ($item) {
        return [
          'worker_id' => $item->worker_id,
          'worker_name' => $item->worker ? $item->worker->nombre_completo : 'N/A',
          'estimated_hours' => $item->estimated_hours,
          'actual_hours' => $item->actual_hours,
          'status' => $item->status,
          'planned_start_datetime' => $item->planned_start_datetime,
          'planned_end_datetime' => $item->planned_end_datetime,
          'actual_start_datetime' => $item->actual_start_datetime,
          'actual_end_datetime' => $item->actual_end_datetime,
        ];
      })->values();

      $consolidated[] = [
        'group_number' => $groupNumber,
        'description' => $description,
        'total_estimated_hours' => round($totalEstimatedHours, 2),
        'total_actual_hours' => round($totalActualHours, 2),
        'remaining_hours' => round($totalEstimatedHours - $totalActualHours, 2),
        'progress_percentage' => $progressPercentage,
        'status' => $groupStatus,
        'workers_count' => $items->count(),
        'workers' => $workers,
      ];
    }

    return $consolidated;
  }

  private function determineGroupStatus($statuses)
  {
    if ($statuses->contains('in_progress')) {
      return 'in_progress';
    }

    if ($statuses->every(fn($status) => $status === 'completed')) {
      return 'completed';
    }

    if ($statuses->contains('paused')) {
      return 'paused';
    }

    return 'pending';
  }
}
