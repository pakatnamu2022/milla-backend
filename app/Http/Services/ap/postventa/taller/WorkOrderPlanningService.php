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

    // Ejecutar validaciones antes de crear
    $this->validateWorkerSchedule($data);

    $planning = ApWorkOrderPlanning::create($data);
    return new WorkOrderPlanningResource($planning->load(['worker', 'workOrder']));
  }

  /**
   * Valida el horario del trabajador según las reglas de negocio
   */
  private function validateWorkerSchedule(array $data): void
  {
    $workerId = $data['worker_id'];
    $type = $data['type'];
    $plannedStart = Carbon::parse($data['planned_start_datetime']);
    $plannedEnd = Carbon::parse($data['planned_end_datetime']);

    // Obtener fecha del día para las validaciones
    $currentDate = $plannedStart->format('Y-m-d');

    // Obtener todos los trabajos del trabajador para ese día
    $workerPlannings = ApWorkOrderPlanning::where('worker_id', $workerId)
      ->whereDate('planned_start_datetime', $currentDate)
      ->orderBy('planned_end_datetime', 'desc')
      ->get();

    // 1. Validar solapamiento de horarios
    $this->validateNoOverlap($workerPlannings, $plannedStart, $plannedEnd);

    // 2. Validaciones específicas según el tipo
    if ($type === 'external') {
      $this->validateExternalType($workerPlannings, $plannedStart, $plannedEnd);
    } else {
      // type === 'internal'
      $this->validateInternalType($plannedStart, $plannedEnd);
    }
  }

  /**
   * Valida que no haya solapamiento de horarios
   */
  private function validateNoOverlap($existingPlannings, Carbon $plannedStart, Carbon $plannedEnd): void
  {
    foreach ($existingPlannings as $existing) {
      $existingStart = Carbon::parse($existing->planned_start_datetime);
      $existingEnd = Carbon::parse($existing->planned_end_datetime);

      // Verificar si hay solapamiento
      if (
        ($plannedStart >= $existingStart && $plannedStart < $existingEnd) ||
        ($plannedEnd > $existingStart && $plannedEnd <= $existingEnd) ||
        ($plannedStart <= $existingStart && $plannedEnd >= $existingEnd)
      ) {
        throw new Exception(
          "El horario asignado ({$plannedStart->format('H:i')} - {$plannedEnd->format('H:i')}) " .
          "se solapa con un trabajo existente ({$existingStart->format('H:i')} - {$existingEnd->format('H:i')}). " .
          "No se pueden asignar horarios duplicados o solapados para el mismo trabajador."
        );
      }
    }
  }

  /**
   * Valida las reglas para tipo "external"
   */
  private function validateExternalType($workerPlannings, Carbon $plannedStart, Carbon $plannedEnd): void
  {
    // 1. Verificar que tenga al menos 1 trabajo "internal" ese día
    $hasInternalWork = $workerPlannings->where('type', 'internal')->count() > 0;
    if (!$hasInternalWork) {
      throw new Exception(
        'No se puede asignar un trabajo de tipo "external" si el trabajador no tiene ' .
        'al menos 1 trabajo de tipo "internal" registrado para este día.'
      );
    }

    // 2. Verificar que no tenga ya un trabajo "external" ese día (solo 1 por día)
    $hasExternalWork = $workerPlannings->where('type', 'external')->count() > 0;
    if ($hasExternalWork) {
      throw new Exception(
        'El trabajador ya tiene un trabajo de tipo "external" asignado para este día. ' .
        'Solo se permite 1 trabajo externo por día.'
      );
    }

    // 3. Obtener el último trabajo "internal" del día
    $lastInternalWork = $workerPlannings->where('type', 'internal')->first(); // Ya está ordenado por planned_end_datetime desc

    if ($lastInternalWork) {
      $lastEndTime = Carbon::parse($lastInternalWork->planned_end_datetime);
      $endOfWorkDay = Carbon::parse($plannedStart->format('Y-m-d') . ' 18:00:00'); // 6pm

      // 4. Verificar si aún le falta tiempo hasta las 6pm
      if ($lastEndTime->lt($endOfWorkDay)) {
        $remainingMinutes = $lastEndTime->diffInMinutes($endOfWorkDay);
        $remainingHours = round($remainingMinutes / 60, 2);

        throw new Exception(
          "El trabajador terminó su último trabajo a las {$lastEndTime->format('H:i')}. " .
          "Aún tiene {$remainingHours} horas disponibles hasta las 18:00 (fin de jornada). " .
          "Debe asignarle trabajos con normalidad en el rango de {$lastEndTime->format('H:i')} a 18:00 " .
          "antes de poder asignar trabajo de tipo excepcional."
        );
      }
    }
  }

  /**
   * Valida las reglas para tipo "internal"
   */
  private function validateInternalType(Carbon $plannedStart, Carbon $plannedEnd): void
  {
    $startTime = $plannedStart->format('H:i');
    $endTime = $plannedEnd->format('H:i');

    // Horarios válidos para type "internal":
    // - Mañana: 08:00 - 13:00 (1pm)
    // - Tarde: 14:24 - 18:00 (6pm)

    $morningStart = '08:00';
    $morningEnd = '13:00';
    $afternoonStart = '14:24';
    $afternoonEnd = '18:00';

    $isInMorningShift = $startTime >= $morningStart && $endTime <= $morningEnd;
    $isInAfternoonShift = $startTime >= $afternoonStart && $endTime <= $afternoonEnd;

    // Validar que el horario esté dentro de los rangos permitidos
    if (!$isInMorningShift && !$isInAfternoonShift) {
      throw new Exception(
        "Los trabajos de tipo 'internal' deben estar dentro de los horarios permitidos: " .
        "Mañana (08:00 - 13:00) o Tarde (14:24 - 18:00). " .
        "El horario asignado ({$startTime} - {$endTime}) está fuera de estos rangos."
      );
    }

    // Validar que no cruce entre turnos (mañana -> tarde)
    if ($startTime < $morningEnd && $endTime > $afternoonStart) {
      throw new Exception(
        "El horario asignado ({$startTime} - {$endTime}) cruza entre el turno de mañana y tarde. " .
        "Debe crear trabajos separados para cada turno."
      );
    }
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
