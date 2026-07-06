<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderPartDelivery;
use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use App\Models\ap\postventa\taller\ApWorkOrderPlanningSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    return DB::transaction(function () use ($data) {
      $data['type'] = $data['type'] ?? 'internal';

      $workOrder = ApWorkOrder::find($data['work_order_id']);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $validateReceipt = $workOrder->shouldValidateReceipt();

      $workOrder->ensureCanBeModified();

      if ($workOrder->vehicleInspection === null && $validateReceipt) {
        throw new Exception('No se puede planificar un trabajo si la OT no tiene una recepción de vehículo asociada.');
      }

      // Calcular planned_end_datetime si es necesario
      $data = $this->calculatePlannedEndDatetime($data);

      // Para tipo 'internal', dividir automáticamente si cruza almuerzo o excede horario laboral
      if ($data['type'] === 'internal') {
        $timeBlocks = $this->splitIntoTimeBlocks($data);

        // Validar cada bloque antes de crear
        foreach ($timeBlocks as $block) {
          $this->validateWorkerSchedule($block);
        }

        // Crear todos los bloques
        $plannings = [];
        foreach ($timeBlocks as $block) {
          $plannings[] = ApWorkOrderPlanning::create($block);
        }

        // Retornar el primer planning creado con su relación
        return new WorkOrderPlanningResource($plannings[0]->load(['worker', 'workOrder']));
      }

      // Para tipo 'external', crear normalmente
      $this->validateWorkerSchedule($data);
      $planning = ApWorkOrderPlanning::create($data);
      return new WorkOrderPlanningResource($planning->load(['worker', 'workOrder']));
    });
  }

  /**
   * Calcula el planned_end_datetime basado en estimated_hours y planned_start_datetime
   */
  private function calculatePlannedEndDatetime(array $data): array
  {
    if (!isset($data['planned_end_datetime']) && isset($data['estimated_hours']) && isset($data['planned_start_datetime'])) {
      $estimatedHours = floatval($data['estimated_hours']);
      $minutes = $estimatedHours * 60;
      $data['planned_end_datetime'] = Carbon::parse($data['planned_start_datetime'])->addMinutes($minutes);
    }

    return $data;
  }

  /**
   * Divide un rango horario en múltiples bloques si cruza almuerzo o excede horario laboral
   */
  private function splitIntoTimeBlocks(array $data): array
  {
    // Truncar segundos para evitar problemas con decimales
    $start = Carbon::parse($data['planned_start_datetime'])->startOfMinute();
    $end = Carbon::parse($data['planned_end_datetime'])->startOfMinute();
    $currentDate = $start->format('Y-m-d');

    // Construir los límites de horarios usando las constantes
    $lunchStart = Carbon::parse($currentDate . ' ' . ApWorkOrderPlanning::LUNCH_START_TIME);
    $lunchEnd = Carbon::parse($currentDate . ' ' . ApWorkOrderPlanning::LUNCH_END_TIME);
    $workEnd = Carbon::parse($currentDate . ' ' . ApWorkOrderPlanning::WORK_END_TIME);
    $nextDayWorkStart = Carbon::parse($start->format('Y-m-d') . ' ' . ApWorkOrderPlanning::WORK_START_TIME)->addDay();

    $blocks = [];
    $currentStart = $start->copy();

    // Caso 1: El rango no cruza almuerzo ni excede horario - retornar tal cual
    if ($end <= $lunchStart || ($currentStart >= $lunchEnd && $end <= $workEnd)) {
      return [$data];
    }

    // Caso 2: Cruza almuerzo
    if ($currentStart < $lunchStart && $end > $lunchStart) {
      // Calcular minutos totales solicitados
      $totalMinutes = $currentStart->diffInMinutes($end);

      // Calcular minutos del primer bloque (antes del almuerzo)
      $block1Minutes = $currentStart->diffInMinutes($lunchStart);

      // Calcular minutos restantes después del primer bloque
      $remainingMinutes = $totalMinutes - $block1Minutes;

      // Calcular el verdadero end time después del almuerzo
      $actualEnd = $lunchEnd->copy()->addMinutes($remainingMinutes);

      // Verificar si el verdadero end time excede el horario laboral
      if ($actualEnd <= $workEnd) {
        // Caso 2a: Cruza almuerzo pero no excede horario laboral - dividir en 2 bloques
        $blocks[] = $this->createBlock($data, $currentStart, $lunchStart);

        if ($remainingMinutes > 0) {
          $blocks[] = $this->createBlock($data, $lunchEnd, $actualEnd);
        }

        return $blocks;
      } else {
        // Caso 2b: Cruza almuerzo Y excede horario laboral - dividir en 3 bloques
        // Bloque 1: desde inicio hasta inicio de almuerzo
        $blocks[] = $this->createBlock($data, $currentStart, $lunchStart);

        // Bloque 2: desde fin de almuerzo hasta fin de jornada (6pm)
        $blocks[] = $this->createBlock($data, $lunchEnd, $workEnd);

        // Calcular minutos del bloque 2
        $block2Minutes = $lunchEnd->diffInMinutes($workEnd);

        // Calcular minutos restantes para el día siguiente
        $nextDayMinutes = $remainingMinutes - $block2Minutes;

        // Bloque 3: las horas restantes van al día siguiente desde 8am
        if ($nextDayMinutes > 0) {
          $nextDayEnd = $nextDayWorkStart->copy()->addMinutes($nextDayMinutes);
          $blocks[] = $this->createBlock($data, $nextDayWorkStart, $nextDayEnd);
        }

        return $blocks;
      }
    }

    // Caso 3: No cruza almuerzo pero excede horario laboral
    if (($currentStart >= $lunchEnd || $end <= $lunchStart) && $end > $workEnd) {
      // Bloque 1: desde inicio hasta fin de jornada (6pm)
      $blocks[] = $this->createBlock($data, $currentStart, $workEnd);

      // Bloque 2: las horas restantes van al día siguiente desde 8am
      $remainingMinutes = $workEnd->diffInMinutes($end);
      $nextDayEnd = $nextDayWorkStart->copy()->addMinutes($remainingMinutes);
      $blocks[] = $this->createBlock($data, $nextDayWorkStart, $nextDayEnd);

      return $blocks;
    }

    // Por defecto, retornar el bloque original si no entra en ningún caso
    return [$data];
  }

  /**
   * Crea un bloque de planificación con las fechas y horas especificadas
   */
  private function createBlock(array $baseData, Carbon $start, Carbon $end): array
  {
    $block = $baseData;
    $block['planned_start_datetime'] = $start->format('Y-m-d H:i:s');
    $block['planned_end_datetime'] = $end->format('Y-m-d H:i:s');

    // Calcular estimated_hours basado en la diferencia de tiempo
    $minutes = $start->diffInMinutes($end);
    $block['estimated_hours'] = round($minutes / 60, 2);

    return $block;
  }

  /**
   * Valida el horario del trabajador según las reglas de negocio
   */
  private function validateWorkerSchedule(array $data, ?int $excludeId = null): void
  {
    $workerId = $data['worker_id'];
    $type = $data['type'];
    $plannedStart = Carbon::parse($data['planned_start_datetime'])->startOfMinute();
    $plannedEnd = Carbon::parse($data['planned_end_datetime'])->startOfMinute();

    // Obtener fecha del día para las validaciones
    $currentDate = $plannedStart->format('Y-m-d');

    // Obtener todos los trabajos del trabajador para ese día
    $query = ApWorkOrderPlanning::where('worker_id', $workerId)
      ->whereDate('planned_start_datetime', $currentDate)
      ->orderBy('planned_end_datetime', 'desc');

    // Excluir el ID actual si se está editando
    if ($excludeId !== null) {
      $query->where('id', '!=', $excludeId);
    }

    $workerPlannings = $query->get();

    // 1. Validar solapamiento de horarios
    $this->validateNoOverlap($workerPlannings, $plannedStart, $plannedEnd, $type);

    // 2. Validar que el nuevo trabajo empiece después del último asignado (solo para tipo 'internal')
    if ($type === 'internal') {
      $lastInternalWork = $workerPlannings->where('type', 'internal')->first(); // Ya ordenado desc por planned_end_datetime

      if ($lastInternalWork) {
        $lastEndTime = Carbon::parse($lastInternalWork->planned_end_datetime)->startOfMinute();

        // El nuevo trabajo debe comenzar desde la hora fin del último trabajo en adelante
        if ($plannedStart->lt($lastEndTime)) {
          throw new Exception(
            "Ya existe un trabajo asignado para este trabajador que termina a las {$lastEndTime->format('H:i')}. " .
            "El nuevo trabajo debe comenzar a partir de las {$lastEndTime->format('H:i')} en adelante. " .
            "El horario que intenta asignar comienza a las {$plannedStart->format('H:i')}."
          );
        }
      }
    }

    // 3. Validaciones específicas según el tipo
    if ($type === 'external') {
      $this->validateExternalType($workerPlannings, $plannedStart, $plannedEnd);
    } else {
      // type === 'internal'
      $this->validateInternalType($plannedStart, $plannedEnd);
    }
  }

  /**
   * Valida que no haya solapamiento de horarios entre trabajos del mismo tipo
   */
  private function validateNoOverlap($existingPlannings, Carbon $plannedStart, Carbon $plannedEnd, string $type): void
  {
    // Filtrar solo los trabajos del mismo tipo
    $samePlannings = $existingPlannings->where('type', $type);

    // Normalizar los timestamps del nuevo trabajo (eliminar segundos y microsegundos)
    $plannedStart = $plannedStart->copy()->startOfMinute();
    $plannedEnd = $plannedEnd->copy()->startOfMinute();

    foreach ($samePlannings as $existing) {
      // Normalizar los timestamps del trabajo existente (eliminar segundos y microsegundos)
      $existingStart = Carbon::parse($existing->planned_start_datetime)->startOfMinute();
      $existingEnd = Carbon::parse($existing->planned_end_datetime)->startOfMinute();

      // Verificar si hay solapamiento
      if (
        ($plannedStart >= $existingStart && $plannedStart < $existingEnd) ||
        ($plannedEnd > $existingStart && $plannedEnd <= $existingEnd) ||
        ($plannedStart <= $existingStart && $plannedEnd >= $existingEnd)
      ) {
        throw new Exception(
          "El horario asignado ({$plannedStart->format('H:i')} - {$plannedEnd->format('H:i')}) " .
          "se solapa con un trabajo existente ({$existingStart->format('H:i')} - {$existingEnd->format('H:i')}). " .
          "No se pueden asignar horarios duplicados o solapados para el mismo trabajador en el mismo tipo de trabajo."
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
        'No se puede asignar un trabajo excepcional si el trabajador no tiene ' .
        'al menos 1 trabajo registrado para este día.'
      );
    }

    // 2. Obtener trabajos "external" existentes ese día
    $externalWorks = $workerPlannings->where('type', 'external');

    if ($externalWorks->count() > 0) {
      // Si ya hay trabajos excepcionales, validar que el nuevo comience después del último
      $lastExternalWork = $externalWorks->first(); // Ya está ordenado por planned_end_datetime desc

      // Validar que el último trabajo excepcional esté completado
      if ($lastExternalWork->status !== 'completed') {
        throw new Exception(
          "No se puede asignar un nuevo trabajo excepcional. " .
          "El último trabajo excepcional (desde las {$lastExternalWork->planned_start_datetime->format('H:i')} " .
          "hasta las {$lastExternalWork->planned_end_datetime->format('H:i')}) aún no ha sido completado. " .
          "Debe completar el trabajo excepcional actual antes de asignar uno nuevo."
        );
      }

      $lastExternalEndTime = Carbon::parse($lastExternalWork->planned_end_datetime);

      // El nuevo trabajo excepcional debe comenzar desde la hora fin del último trabajo excepcional en adelante
      if ($plannedStart->lt($lastExternalEndTime)) {
        throw new Exception(
          "El nuevo trabajo excepcional debe comenzar a partir de las {$lastExternalEndTime->format('H:i')} " .
          "(hora fin del último trabajo excepcional). " .
          "El horario asignado comienza a las {$plannedStart->format('H:i')}."
        );
      }
    } else {
      // Si no hay trabajos excepcionales previos, validar con el último trabajo "internal"
      // 3. Obtener el último trabajo "internal" del día
      $lastInternalWork = $workerPlannings->where('type', 'internal')->first(); // Ya está ordenado por planned_end_datetime desc

      if ($lastInternalWork) {
        $lastEndTime = Carbon::parse($lastInternalWork->planned_end_datetime);
        $endOfWorkDay = Carbon::parse($plannedStart->format('Y-m-d') . ' ' . ApWorkOrderPlanning::WORK_END_TIME);

        // 4. Verificar que el último trabajo termine exactamente a las 6pm
        if (!$lastEndTime->equalTo($endOfWorkDay)) {
          $remainingMinutes = $lastEndTime->diffInMinutes($endOfWorkDay);
          $remainingHours = round($remainingMinutes / 60, 2);

          throw new Exception(
            "El trabajador terminó su último trabajo a las {$lastEndTime->format('H:i')}. " .
            "Para asignar trabajo excepcional, el último trabajo del día debe terminar exactamente a las " . ApWorkOrderPlanning::WORK_END_TIME . " (fin de jornada). " .
            "Actualmente hay una diferencia de {$remainingHours} horas. " .
            "Debe asignarle trabajos con normalidad hasta completar la jornada antes de poder asignar trabajo de tipo excepcional."
          );
        }
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

    // Usar constantes del modelo
    $morningStart = ApWorkOrderPlanning::WORK_START_TIME;
    $morningEnd = ApWorkOrderPlanning::LUNCH_START_TIME;
    $afternoonStart = ApWorkOrderPlanning::LUNCH_END_TIME;
    $afternoonEnd = ApWorkOrderPlanning::WORK_END_TIME;

    $isInMorningShift = $startTime >= $morningStart && $endTime <= $morningEnd;
    $isInAfternoonShift = $startTime >= $afternoonStart && $endTime <= $afternoonEnd;

    // Validar que el horario esté dentro de los rangos permitidos
    // Ya no validamos si cruza turnos porque ahora se divide automáticamente
    if (!$isInMorningShift && !$isInAfternoonShift) {
      throw new Exception(
        "Los trabajos deben estar dentro de los horarios permitidos: " .
        "Mañana ({$morningStart} - {$morningEnd}) o Tarde ({$afternoonStart} - {$afternoonEnd}). " .
        "El horario asignado ({$startTime} - {$endTime}) está fuera de estos rangos."
      );
    }
  }

  public function show($id)
  {
    return new WorkOrderPlanningResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $planning = $this->find($data['id']);
      $workOrder = ApWorkOrder::find($planning->work_order_id);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureCanBeModified();

      if (!$planning) {
        throw new Exception('Planificación no encontrada');
      }

      if ($planning->status === 'in_progress') {
        throw new Exception('No se puede editar esta planificación porque el técnico ya ha iniciado el trabajo.');
      }

      if ($planning->status === 'completed') {
        throw new Exception('No se puede editar esta planificación porque el trabajo ya ha sido completado.');
      }

      if ($planning->status === 'canceled') {
        throw new Exception('No se puede editar esta planificación porque el trabajo ya ha sido cancelado.');
      }

      // Parsear las fechas
      $plannedStart = Carbon::parse($data['planned_start_datetime']);
      $plannedEnd = Carbon::parse($data['planned_end_datetime']);

      // Validar que la fecha final no sea menor que la inicial
      if ($plannedEnd->lessThanOrEqualTo($plannedStart)) {
        throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio.');
      }

      // Validar que estén dentro del horario laboral
      $this->validateWorkingHours($plannedStart, $plannedEnd);

      // Calcular las horas estimadas basándose en la diferencia de tiempo
      $minutesDiff = $plannedStart->diffInMinutes($plannedEnd);
      $estimatedHours = round($minutesDiff / 60, 2);

      // Preparar datos para validación mergeando con datos existentes
      $validationData = [
        'worker_id' => $planning->worker_id,
        'type' => $planning->type,
        'estimated_hours' => $estimatedHours,
        'planned_start_datetime' => $data['planned_start_datetime'],
        'planned_end_datetime' => $data['planned_end_datetime'],
      ];

      // Validar antes de actualizar (excluyendo el ID actual)
      $this->validateWorkerSchedule($validationData, $planning->id);

      // Preparar datos finales para actualización
      $updateData = [
        'planned_start_datetime' => $data['planned_start_datetime'],
        'planned_end_datetime' => $data['planned_end_datetime'],
        'estimated_hours' => $estimatedHours,
      ];

      $planning->update($updateData);
      return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
    });
  }

  public function destroy($id)
  {
    return DB::transaction(function () use ($id) {
      $planning = $this->find($id);
      $workOrder =
        ApWorkOrder::find($planning->work_order_id);

      if (!$workOrder) {
        throw new Exception('Orden de trabajo no encontrada');
      }

      $workOrder->ensureCanBeModified();

      if ($planning->status === 'in_progress') {
        throw new Exception('No se puede eliminar esta planificación porque el técnico ya ha iniciado el trabajo.');
      }

      if ($planning->status === 'completed') {
        throw new Exception('No se puede eliminar esta planificación porque el trabajo ya ha sido completado.');
      }

      if ($planning->status === 'canceled') {
        throw new Exception('No se puede eliminar esta planificación porque el trabajo ya ha sido cancelado.');
      }

      // Validar que el técnico no tenga asignaciones de repuestos pendientes por confirmar
      $this->validatePartDeliveries($planning, true);

      $planning->delete();

      // Verificar si todos los trabajos restantes de la OT están completados
      // Si es así, marcar la OT como trabajo terminado
      $pendingPlannings = $workOrder->plannings()
        ->whereIn('status', ['planned', 'in_progress'])
        ->count();

      if ($pendingPlannings === 0) {
        // Verificar que haya al menos un trabajo completado
        $completedPlannings = $workOrder->plannings()
          ->where('status', 'completed')
          ->count();

        if ($completedPlannings > 0) {
          $workOrder->status_id = ApMasters::END_WORK_WORK_ORDER_ID;
          $workOrder->save();
        }
      }

      return response()->json(['message' => 'Planificación eliminada correctamente']);
    });
  }

  /**
   * Valida que las fechas estén dentro del horario laboral
   */
  private function validateWorkingHours(Carbon $plannedStart, Carbon $plannedEnd): void
  {
    $startTime = $plannedStart->format('H:i');
    $endTime = $plannedEnd->format('H:i');

    // Usar constantes del modelo
    $workStart = ApWorkOrderPlanning::WORK_START_TIME;
    $lunchStart = ApWorkOrderPlanning::LUNCH_START_TIME;
    $lunchEnd = ApWorkOrderPlanning::LUNCH_END_TIME;
    $workEnd = ApWorkOrderPlanning::WORK_END_TIME;

    // Validar que ambas fechas sean del mismo día
    if ($plannedStart->format('Y-m-d') !== $plannedEnd->format('Y-m-d')) {
      throw new Exception(
        "Las fechas de inicio y fin deben ser del mismo día."
      );
    }

    // Validar que la hora de inicio esté dentro del horario laboral
    if ($startTime < $workStart || $startTime >= $workEnd) {
      throw new Exception(
        "La hora de inicio ($startTime) debe estar dentro del horario laboral ($workStart - $workEnd)."
      );
    }

    // Validar que la hora de fin esté dentro del horario laboral
    if ($endTime <= $workStart || $endTime > $workEnd) {
      throw new Exception(
        "La hora de fin ($endTime) debe estar dentro del horario laboral ($workStart - $workEnd)."
      );
    }

    // Validar que NO caiga dentro del periodo de almuerzo (ni inicio, ni fin, ni que lo cruce)
    // Rechazar cualquier horario que toque el periodo de almuerzo
    if (
      // Si la hora de inicio está dentro del almuerzo
      ($startTime >= $lunchStart && $startTime < $lunchEnd) ||
      // Si la hora de fin está dentro del almuerzo
      ($endTime > $lunchStart && $endTime <= $lunchEnd) ||
      // Si el horario cruza el periodo de almuerzo (empieza antes y termina después)
      ($startTime < $lunchStart && $endTime > $lunchStart)
    ) {
      throw new Exception(
        "El horario asignado no puede caer ni cruzar el periodo de almuerzo ($lunchStart - $lunchEnd). " .
        "Los horarios válidos son: Mañana ($workStart - $lunchStart) o Tarde ($lunchEnd - $workEnd)."
      );
    }
  }

  public function consolidated($workOrderId)
  {
    // Obtener todos los registros de planificación para la orden de trabajo
    $plannings = ApWorkOrderPlanning::with(['worker'])
      ->where('work_order_id', $workOrderId)
      ->where('status', '!=', 'canceled')
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

      // Obtener información de trabajadores agrupados por worker_id (sin duplicados)
      $workersByWorkerId = $items->groupBy('worker_id');

      $workers = $workersByWorkerId->map(function ($workerItems, $workerId) {
        // Sumar las horas del mismo trabajador
        $totalWorkerEstimatedHours = $workerItems->sum('estimated_hours');
        $totalWorkerActualHours = $workerItems->sum('actual_hours');

        // Tomar el primer item para obtener datos generales
        $firstItem = $workerItems->first();

        // Determinar el estado del trabajador
        $workerStatuses = $workerItems->pluck('status')->unique();
        $workerStatus = $this->determineGroupStatus($workerStatuses);

        return [
          'worker_id' => $workerId,
          'worker_name' => $firstItem->worker ? $firstItem->worker->nombre_completo : 'N/A',
          'estimated_hours' => round($totalWorkerEstimatedHours, 2),
          'actual_hours' => round($totalWorkerActualHours, 2),
          'status' => $workerStatus,
          'planned_start_datetime' => $firstItem->planned_start_datetime,
          'planned_end_datetime' => $workerItems->last()->planned_end_datetime,
          'actual_start_datetime' => $firstItem->actual_start_datetime,
          'actual_end_datetime' => $workerItems->last()->actual_end_datetime,
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
        'workers_count' => $workersByWorkerId->count(),
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

  /**
   * Obtener lista consolidada de trabajadores únicos que participaron en una orden de trabajo
   * Sin duplicados
   */
  public function getWorkers($workOrderId)
  {
    // Obtener todos los registros de planificación para la orden de trabajo
    $plannings = ApWorkOrderPlanning::with(['worker'])
      ->where('work_order_id', $workOrderId)
      ->get();

    if ($plannings->isEmpty()) {
      return [];
    }

    // Agrupar por worker_id para obtener trabajadores únicos
    $grouped = $plannings->groupBy('worker_id');

    $workers = [];

    foreach ($grouped as $workerId => $workerPlannings) {
      $firstPlanning = $workerPlannings->first();

      // Calcular totales del trabajador
      $totalEstimatedHours = $workerPlannings->sum('estimated_hours');
      $totalActualHours = $workerPlannings->sum('actual_hours');

      // Contar trabajos por estado
      $totalJobs = $workerPlannings->count();
      $completedJobs = $workerPlannings->where('status', 'completed')->count();
      $inProgressJobs = $workerPlannings->where('status', 'in_progress')->count();
      $pendingJobs = $workerPlannings->where('status', 'pending')->count();

      // Calcular porcentaje de progreso
      $progressPercentage = $totalEstimatedHours > 0
        ? round(($totalActualHours / $totalEstimatedHours) * 100, 2)
        : 0;

      $workers[] = [
        'worker_id' => $workerId,
        'worker_name' => $firstPlanning->worker ? $firstPlanning->worker->nombre_completo : 'N/A',
        'total_jobs' => $totalJobs,
        'completed_jobs' => $completedJobs,
        'in_progress_jobs' => $inProgressJobs,
        'pending_jobs' => $pendingJobs,
        'total_estimated_hours' => round($totalEstimatedHours, 2),
        'total_actual_hours' => round($totalActualHours, 2),
        'remaining_hours' => round($totalEstimatedHours - $totalActualHours, 2),
        'progress_percentage' => $progressPercentage,
      ];
    }

    // Ordenar por nombre del trabajador
    usort($workers, function ($a, $b) {
      return strcmp($a['worker_name'], $b['worker_name']);
    });

    return $workers;
  }

  /**
   * Permite al supervisor finalizar manualmente un trabajo cuando el trabajador olvida hacerlo
   * Recibe la hora fin y valida que no sea mayor a la hora programada
   * Si se finaliza después de las 6pm pero el mismo día, ajusta la hora a las 6pm
   * Si ya es otro día, no permite y debe hacerlo el encargado
   */
  public function supervisorComplete($id, array $data)
  {
    $planning = $this->find($id);

    // Validar que el trabajo esté en progreso
    if ($planning->status !== 'in_progress') {
      throw new Exception('Solo se pueden completar trabajos que estén en progreso.');
    }

    $endDatetime = Carbon::parse($data['end_datetime']);
    $plannedEndDatetime = Carbon::parse($planning->planned_end_datetime);

    // Validar que la fecha sea la misma que la programada
    if ($endDatetime->format('Y-m-d') !== $plannedEndDatetime->format('Y-m-d')) {
      throw new Exception(
        'La fecha debe ser la misma en la que se programó el trabajo (' .
        $plannedEndDatetime->format('d/m/Y') . ').'
      );
    }

    // Obtener la hora de inicio real del trabajo (de la sesión activa o actual_start_datetime)
    $activeSession = $planning->activeSession();
    $startDatetime = $activeSession
      ? Carbon::parse($activeSession->start_datetime)
      : Carbon::parse($planning->actual_start_datetime);

    // Validar que la hora de finalización sea mayor que la hora de inicio
    if ($endDatetime->lessThanOrEqualTo($startDatetime)) {
      throw new Exception(
        'La hora de finalización (' . $endDatetime->format('H:i') . ') ' .
        'debe ser mayor a la hora de inicio del trabajo (' . $startDatetime->format('H:i') . ').'
      );
    }

    // Si la hora de finalización es después de las 6pm, ajustarla a las 6pm
    $workEndTime = Carbon::parse($endDatetime->format('Y-m-d') . ' ' . ApWorkOrderPlanning::WORK_END_TIME);
    if ($endDatetime->greaterThan($workEndTime)) {
      $endDatetime = $workEndTime;
    }

    // 1. Finalizar sesión activa si existe
    $activeSession = $planning->activeSession();
    if ($activeSession) {
      $activeSession->end_datetime = $endDatetime;

      // Calcular horas trabajadas de la sesión
      $startDatetime = Carbon::parse($activeSession->start_datetime);
      $minutesWorked = $startDatetime->diffInMinutes($endDatetime);
      $activeSession->hours_worked = round($minutesWorked / 60, 2);
      $activeSession->status = 'completed';

      $activeSession->save();
    }

    // 2. Calcular actual_hours sumando todas las sesiones
    $planning->actual_hours = $planning->calculateTotalHoursWorked();

    // 3. Actualizar actual_end_datetime
    $planning->actual_end_datetime = $endDatetime;

    // 4. Cambiar estado a completed
    $planning->status = 'completed';

    $planning->save();

    // 5. Verificar y actualizar estado de Work Order si todos los trabajos están completados
    $planning->checkAndUpdateWorkOrderStatus();

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Permite al supervisor crear y finalizar automáticamente un trabajo que el técnico
   * nunca llegó a iniciar (olvidó dar inicio), respetando el horario planificado
   * (planned_start_datetime y planned_end_datetime) como si se hubiera trabajado en ese horario.
   */
  public function autoComplete($id)
  {
    return DB::transaction(function () use ($id) {
      $planning = $this->find($id);

      if ($planning->status !== 'planned') {
        throw new Exception(
          "Solo se pueden autocompletar trabajos que aún no han sido iniciados. " .
          "Este trabajo se encuentra en estado \"{$planning->status}\"."
        );
      }

      if ($planning->sessions->isNotEmpty()) {
        throw new Exception(
          'Este trabajo ya tiene sesiones registradas. ' .
          'Use "completar" o "supervisor-complete" según corresponda.'
        );
      }

      $plannedStart = Carbon::parse($planning->planned_start_datetime);
      $plannedEnd = Carbon::parse($planning->planned_end_datetime);

      // Solo se puede autocompletar una vez que el horario programado ya finalizó
      if (Carbon::now()->lt($plannedEnd)) {
        throw new Exception(
          "No se puede autocompletar este trabajo porque el horario programado " .
          "(hasta las {$plannedEnd->format('H:i')}) aún no ha finalizado."
        );
      }

      // Crear la sesión completa respetando el horario planificado
      $minutesWorked = $plannedStart->diffInMinutes($plannedEnd);
      ApWorkOrderPlanningSession::create([
        'work_order_planning_id' => $planning->id,
        'start_datetime' => $plannedStart,
        'end_datetime' => $plannedEnd,
        'hours_worked' => round($minutesWorked / 60, 2),
        'status' => 'completed',
        'notes' => 'Sesión generada automáticamente por el supervisor porque el técnico no registró el inicio del trabajo.',
      ]);

      // Actualizar el planning con los datos del horario planificado
      $planning->actual_start_datetime = $plannedStart;
      $planning->actual_end_datetime = $plannedEnd;
      $planning->actual_hours = $planning->calculateTotalHoursWorked();
      $planning->status = 'completed';
      $planning->save();

      // Verificar y actualizar estado de Work Order si todos los trabajos están completados
      $planning->checkAndUpdateWorkOrderStatus();

      return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
    });
  }

  /**
   * Cancela un trabajo de planificación
   * Valida que el técnico haya iniciado trabajo y finaliza la última sesión con la hora proporcionada
   */
  public function cancel($id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $planning = $this->find($id);

      // Validar que el trabajo no esté ya cancelado
      if ($planning->status === 'canceled') {
        throw new Exception('Este trabajo ya ha sido cancelado.');
      }

      // Validar que el técnico no tenga asignaciones de repuestos (confirmadas o pendientes)
      $this->validatePartDeliveries($planning, false);

      $actualEndDatetime = Carbon::parse($data['actual_end_datetime']);

      // 1. Validar que la fecha y hora no sea mayor a la actual
      if ($actualEndDatetime->greaterThan(Carbon::now())) {
        throw new Exception(
          'La fecha y hora de finalización (' . $actualEndDatetime->format('d/m/Y H:i') . ') ' .
          'no puede ser mayor a la fecha y hora actual.'
        );
      }

      // 2. Validar que existan sesiones de trabajo
      $sessions = $planning->sessions;
      if ($sessions->isEmpty()) {
        throw new Exception('Solo se anulan registros que ya se encuentran en el trabajo.');
      }

      // 2. Validar horario laboral para actual_end_datetime
      $this->validateWorkingHoursForCancellation($actualEndDatetime);

      // 3. Validar que actual_end_datetime no sea mayor a planned_end_datetime
//      $plannedEndDatetime = Carbon::parse($planning->planned_end_datetime);
//      if ($actualEndDatetime->greaterThan($plannedEndDatetime)) {
//        throw new Exception(
//          'La hora de finalización (' . $actualEndDatetime->format('H:i') . ') ' .
//          'no puede ser mayor a la hora planeada de finalización (' . $plannedEndDatetime->format('H:i') . ').'
//        );
//      }

      // 4. Obtener la última sesión (puede estar activa o pausada)
      $lastSession = $sessions->sortByDesc('start_datetime')->first();

      // 4. Validar que actual_end_datetime sea mayor o igual al último start_datetime
      $lastStartDatetime = Carbon::parse($lastSession->start_datetime);
      if ($actualEndDatetime->lessThan($lastStartDatetime)) {
        throw new Exception(
          'La hora de finalización (' . $actualEndDatetime->format('H:i') . ') ' .
          'debe ser mayor o igual a la hora de inicio del último trabajo (' . $lastStartDatetime->format('H:i') . ').'
        );
      }

      // 5. Finalizar la última sesión si está activa (sin end_datetime)
      if (is_null($lastSession->end_datetime)) {
        $lastSession->end_datetime = $actualEndDatetime;

        // Calcular horas trabajadas de la sesión
        $minutesWorked = $lastStartDatetime->diffInMinutes($actualEndDatetime);
        $lastSession->hours_worked = round($minutesWorked / 60, 2);
        $lastSession->status = 'completed';

        $lastSession->save();
      } else {
        // Si la sesión ya tiene end_datetime (está pausada), actualizar el end_datetime
        $lastSession->end_datetime = $actualEndDatetime;

        // Recalcular horas trabajadas de la sesión
        $minutesWorked = $lastStartDatetime->diffInMinutes($actualEndDatetime);
        $lastSession->hours_worked = round($minutesWorked / 60, 2);
        $lastSession->status = 'completed';

        $lastSession->save();
      }

      // 6. Calcular actual_hours sumando todas las sesiones
      $planning->actual_hours = $planning->calculateTotalHoursWorked();

      // 7. Actualizar el planning con los datos de cancelación
      $planning->actual_end_datetime = $actualEndDatetime;
      if ($planning->status !== 'completed') {
        $planning->planned_end_datetime = $actualEndDatetime; // Liberar al técnico actualizando el horario planeado
      }
      $planning->status = 'canceled';
      $planning->canceled_note = $data['canceled_note'];
      $planning->canceled_by = auth()->id();
      $planning->canceled_at = now();

      $planning->save();

      // 8. Verificar si la OT debe retroceder a estado anterior o marcarla como terminada
      $workOrder = ApWorkOrder::findOrFail($planning->work_order_id);
      $activeOrPlannedWorks = $workOrder->plannings()
        ->whereIn('status', ['planned', 'in_progress'])
        ->count();

      if ($activeOrPlannedWorks === 0) {
        // No hay trabajos activos ni planificados
        // Verificar si hay al menos un trabajo completado
        $completedWorks = $workOrder->plannings()
          ->where('status', 'completed')
          ->count();

        if ($completedWorks > 0) {
          // Si hay trabajos completados, marcar la OT como "trabajo terminado"
          $workOrder->status_id = ApMasters::END_WORK_WORK_ORDER_ID;
        } else {
          // Si NO hay trabajos completados (solo cancelados), retroceder al estado según validación
          $validateReceipt = $workOrder->shouldValidateReceipt();

          if ($validateReceipt) {
            // Si valida recepción, retroceder a estado "recepcionado"
            $workOrder->status_id = ApMasters::RECEIVED_WORK_ORDER_ID;
          } else {
            // Si no valida recepción, retroceder a estado "aperturado"
            $workOrder->status_id = ApMasters::OPENING_WORK_ORDER_ID;
          }
        }

        $workOrder->save();
      }

      return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions', 'canceledBy']));
    });
  }

  /**
   * Valida que la hora esté dentro del horario laboral para cancelación
   */
  private function validateWorkingHoursForCancellation(Carbon $datetime): void
  {
    $time = $datetime->format('H:i');

    // Validar que esté después de las 08:00
    if ($time < ApWorkOrderPlanning::WORK_START_TIME) {
      throw new Exception(
        'La hora de finalización no puede ser anterior a las ' . ApWorkOrderPlanning::WORK_START_TIME . '.'
      );
    }

    // Validar que esté antes de las 18:00
    if ($time > ApWorkOrderPlanning::WORK_END_TIME) {
      throw new Exception(
        'La hora de finalización no puede ser posterior a las ' . ApWorkOrderPlanning::WORK_END_TIME . '.'
      );
    }

    // Validar que NO esté en horario de almuerzo (13:00 - 14:24)
    if ($time >= ApWorkOrderPlanning::LUNCH_START_TIME && $time < ApWorkOrderPlanning::LUNCH_END_TIME) {
      throw new Exception(
        'La hora de finalización no puede estar en horario de almuerzo (' .
        ApWorkOrderPlanning::LUNCH_START_TIME . ' - ' . ApWorkOrderPlanning::LUNCH_END_TIME . ').'
      );
    }
  }

  /**
   * Valida si el técnico tiene asignaciones de repuestos en la orden de trabajo
   * @param ApWorkOrderPlanning $planning
   * @param bool $onlyPending Si es true, solo valida asignaciones pendientes. Si es false, valida todas (confirmadas y pendientes)
   * @throws Exception
   */
  private function validatePartDeliveries(ApWorkOrderPlanning $planning, bool $onlyPending = false): void
  {
    $workOrder = $planning->workOrder;
    $userId = $planning->worker->user->id;

    // Obtener todas las entregas de repuestos para este técnico en esta OT
    $deliveriesQuery = ApWorkOrderPartDelivery::whereHas('workOrderPart', function ($query) use ($workOrder) {
      $query->where('work_order_id', $workOrder->id);
    })->where('delivered_to', $userId);

    // Si solo validamos pendientes, filtrar por is_received = false
    if ($onlyPending) {
      $deliveriesQuery->where('is_received', false);
    }

    $deliveriesCount = $deliveriesQuery->count();

    if ($deliveriesCount > 0) {
      if ($onlyPending) {
        throw new Exception(
          'No se puede eliminar esta planificación porque el técnico tiene asignaciones de repuestos pendientes por confirmar en esta orden de trabajo.'
        );
      } else {
        throw new Exception(
          'No se puede cancelar esta planificación porque el técnico tiene asignaciones de repuestos en esta orden de trabajo.'
        );
      }
    }
  }
}
