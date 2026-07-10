<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningResource;
use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningSessionResource;
use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use App\Models\ap\postventa\taller\ApWorkOrderPlanningSession;
use App\Models\User;
use Exception;

class WorkOrderPlanningSessionService
{
  /**
   * Inicia una nueva sesión de trabajo
   *
   * NOTA: El técnico tiene libertad total para iniciar trabajos en cualquier momento.
   * Las validaciones restrictivas fueron removidas para permitir flexibilidad operativa.
   * El sistema registra los tiempos reales para auditoría.
   *
   * @throws Exception
   */
  public function startSession($planningId, ?string $notes = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // VALIDACIÓN REMOVIDA: Horario laboral (00:00 a 23:59)
    // Razón: El técnico puede iniciar en cualquier momento, se registra el tiempo real
    // $this->validateWorkHours('iniciar');

    // VALIDACIÓN REMOVIDA: No iniciar antes de la hora programada
    // Razón: El técnico puede iniciar en cualquier momento (antes, después o en la hora programada)
    // if ($planning->planned_start_datetime) {
    //   $now = now();
    //   $allowedStartTime = $planning->planned_start_datetime->copy()->subMinutes(0);
    //   if ($now->lt($allowedStartTime)) {
    //     throw new Exception("No puede iniciar el trabajo antes de la hora programada...");
    //   }
    // }

    $userId = auth()->id();
    $user = User::find($userId);

    if (!$user) {
      throw new Exception('Usuario no autenticado o no valido');
    }

    $person = $user->person;

    if (!$person) {
      throw new Exception('El usuario no esta registrada en la tabla trabajadores');
    }

    if ($person->id !== $planning->worker_id) {
      throw new Exception('No tiene permiso para iniciar esta sesión de trabajo');
    }

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    // VALIDACIÓN REMOVIDA: Sesión activa en este mismo trabajo
    // Razón: Permitir múltiples sesiones activas (ya se maneja en el modelo)
    // if ($planning->activeSession()) {
    //   throw new Exception('Ya existe una sesión activa...');
    // }

    // VALIDACIÓN REMOVIDA: Otro trabajo en progreso (trabajos paralelos)
    // Razón: El técnico puede tener múltiples trabajos activos simultáneamente
    // $otherActiveWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
    //   ->where('id', '!=', $planning->id)
    //   ->whereHas('sessions', function ($query) {
    //     $query->where('status', 'in_progress')->whereNull('end_datetime');
    //   })
    //   ->first();
    // if ($otherActiveWork) {
    //   throw new Exception('No puede iniciar este trabajo porque ya tiene otro trabajo en progreso...');
    // }

    // VALIDACIÓN REMOVIDA: No pasó más de la mitad del tiempo asignado
    // Razón: El técnico puede iniciar en cualquier momento, lo importante es la duración real
    // if ($planning->planned_start_datetime && $planning->planned_end_datetime) {
    //   $now = now();
    //   $plannedStart = $planning->planned_start_datetime;
    //   $plannedEnd = $planning->planned_end_datetime;
    //   $totalDurationMinutes = $plannedStart->diffInMinutes($plannedEnd);
    //   $halfDurationMinutes = $totalDurationMinutes / 2;
    //   $timeLimit = $plannedStart->copy()->addMinutes($halfDurationMinutes);
    //   if ($now->gt($timeLimit)) {
    //     throw new Exception('No se puede iniciar el trabajo porque ya pasó más de la mitad del tiempo asignado...');
    //   }
    // }

    // VALIDACIÓN REMOVIDA: Orden cronológico (no adelantar trabajos)
    // Razón: El técnico puede iniciar trabajos en cualquier orden
    // if ($planning->planned_start_datetime) {
    //   $currentPlannedStart = $planning->planned_start_datetime;
    //   $dayStart = $currentPlannedStart->copy()->startOfDay();
    //   $dayEnd = $currentPlannedStart->copy()->endOfDay();
    //   $previousPendingWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
    //     ->where('id', '!=', $planning->id)
    //     ->where('status', 'planned')
    //     ->whereBetween('planned_start_datetime', [$dayStart, $dayEnd])
    //     ->where('planned_start_datetime', '<', $currentPlannedStart)
    //     ->orderBy('planned_start_datetime', 'asc')
    //     ->first();
    //   if ($previousPendingWork) {
    //     throw new Exception("No puede iniciar el trabajo de las {$currentStartTime} porque tiene un trabajo anterior...");
    //   }
    // }

    $planning->startSession($notes);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Pausa el trabajo actual
   *
   * NOTA: El técnico puede pausar en cualquier momento, sin restricciones de horario o fecha.
   *
   * @throws Exception
   */
  public function pauseSession($planningId, ?string $pauseReason = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // VALIDACIÓN REMOVIDA: Horario laboral (00:00 a 23:59)
    // Razón: El técnico puede pausar en cualquier momento
    // $this->validateWorkHours('pausar');

    // VALIDACIÓN REMOVIDA: No ser de fecha pasada
    // Razón: El técnico puede pausar trabajos de días anteriores
    // if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
    //   throw new Exception('No se puede pausar una sesión de un trabajo con fecha planificada pasada');
    // }

    $userId = auth()->id();
    $user = User::find($userId);

    if (!$user) {
      throw new Exception('Usuario no autenticado o no valido');
    }

    $person = $user->person;

    if (!$person) {
      throw new Exception('El usuario no esta registrada en la tabla trabajadores');
    }

    if ($person->id !== $planning->worker_id) {
      throw new Exception('No tiene permiso para iniciar esta sesión de trabajo');
    }

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    $activeSession = $planning->activeSession();
    if (!$activeSession) {
      throw new Exception('No hay una sesión activa para pausar');
    }

    $planning->pauseWork($pauseReason);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Continúa un trabajo pausado
   *
   * NOTA: El técnico puede continuar trabajos en cualquier momento, sin restricciones de horario,
   * fecha ni trabajos activos simultáneos.
   *
   * @throws Exception
   */
  public function continueSession($planningId, ?string $notes = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    // VALIDACIÓN REMOVIDA: Horario laboral (00:00 a 23:59)
    // Razón: El técnico puede continuar en cualquier momento
    // $this->validateWorkHours('continuar');

    // VALIDACIÓN REMOVIDA: No ser de días anteriores
    // Razón: El técnico puede continuar trabajos de días pasados
    // if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
    //   throw new Exception('No se puede continuar un trabajo de días anteriores...');
    // }

    $userId = auth()->id();
    $user = User::find($userId);

    if (!$user) {
      throw new Exception('Usuario no autenticado o no valido');
    }

    $person = $user->person;

    if (!$person) {
      throw new Exception('El usuario no esta registrada en la tabla trabajadores');
    }

    if ($person->id !== $planning->worker_id) {
      throw new Exception('No tiene permiso para continuar esta sesión de trabajo');
    }

    // Validar que el trabajo no esté completado o cancelado
    if (!in_array($planning->status, ['in_progress', 'planned'])) {
      throw new Exception('No se puede continuar un trabajo que ya está completado o cancelado');
    }

    // Verificar que tenga al menos una sesión pausada (si no, debería usar startSession)
    $hasPausedSessions = $planning->sessions()->where('status', 'paused')->exists();
    if (!$hasPausedSessions && $planning->status !== 'planned') {
      throw new Exception('Este trabajo no tiene sesiones pausadas. Use "iniciar" en lugar de "continuar"');
    }

    // VALIDACIÓN REMOVIDA: Otro trabajo en progreso (trabajos paralelos)
    // Razón: El técnico puede tener múltiples trabajos activos simultáneamente
    // $otherActiveWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
    //   ->where('id', '!=', $planning->id)
    //   ->whereHas('sessions', function ($query) {
    //     $query->where('status', 'in_progress')->whereNull('end_datetime');
    //   })
    //   ->first();
    // if ($otherActiveWork) {
    //   throw new Exception('No puede continuar este trabajo porque ya tiene otro trabajo en progreso...');
    // }

    $planning->continueSession($notes);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Completa el trabajo
   *
   * NOTA: El técnico puede completar trabajos en cualquier momento, sin restricciones de horario o fecha.
   *
   * @throws Exception
   */
  public function completeWork($planningId)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // VALIDACIÓN REMOVIDA: Horario laboral (00:00 a 23:59)
    // Razón: El técnico puede completar en cualquier momento
    // $this->validateWorkHours('completar');

    // VALIDACIÓN REMOVIDA: No ser de fecha pasada
    // Razón: El técnico puede completar trabajos de días anteriores
    // if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
    //   throw new Exception('No se puede completar un trabajo con fecha planificada pasada');
    // }

    $userId = auth()->id();
    $user = User::find($userId);

    if (!$user) {
      throw new Exception('Usuario no autenticado o no valido');
    }

    $person = $user->person;

    if (!$person) {
      throw new Exception('El usuario no esta registrada en la tabla trabajadores');
    }

    if ($person->id !== $planning->worker_id) {
      throw new Exception('No tiene permiso para iniciar esta sesión de trabajo');
    }

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    if ($planning->status === 'completed') {
      throw new Exception('El trabajo ya está completado');
    }

    // Validar que tenga una sesión activa antes de completar
    if (!$planning->activeSession()) {
      throw new Exception('No hay una sesión activa para completar. Si tiene sesiones pausadas, primero debe continuar el trabajo.');
    }

    $planning->completeWork();

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Obtiene el estado actual de una planificación con su sesión activa
   */
  public function getStatus($planningId)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder', 'sessions'])->find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    return new WorkOrderPlanningResource($planning);
  }

  /**
   * Lista todas las sesiones de una planificación
   */
  public function listSessions($planningId)
  {
    $planning = ApWorkOrderPlanning::find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    $sessions = $planning->sessions()->orderBy('start_datetime', 'desc')->get();

    return WorkOrderPlanningSessionResource::collection($sessions);
  }

  /**
   * Validar hora de entrada y salida
   *
   * MÉTODO DEPRECATED: Ya no se utiliza después de la liberalización de validaciones.
   * Se mantiene comentado para documentación histórica.
   *
   * Anteriormente validaba que las acciones del técnico estuvieran dentro del horario laboral.
   * Ahora el técnico tiene libertad total para iniciar/pausar/continuar/completar en cualquier momento.
   */
  // private function validateWorkHours(string $event): void
  // {
  //   // Validar que el inicio esté dentro del horario laboral (00:00 a 23:59)
  //   $now = now();
  //   $workStartTime = $now->copy()->setTimeFromTimeString(ApWorkOrderPlanning::WORK_START_TIME);
  //   $workEndTime = $now->copy()->setTimeFromTimeString(ApWorkOrderPlanning::WORK_END_TIME);
  //
  //   if ($now->lessThan($workStartTime)) {
  //     throw new Exception(
  //       'No puede ' . $event . ' el trabajo antes de la hora de entrada (' . ApWorkOrderPlanning::WORK_START_TIME . '). ' .
  //       'Hora actual: ' . $now->format('H:i')
  //     );
  //   }
  //
  //   if ($now->greaterThan($workEndTime)) {
  //     throw new Exception(
  //       'No puede ' . $event . ' el trabajo después de la hora de salida (' . ApWorkOrderPlanning::WORK_END_TIME . '). ' .
  //       'Hora actual: ' . $now->format('H:i')
  //     );
  //   }
  // }
}
