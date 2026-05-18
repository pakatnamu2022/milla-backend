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
   * @throws Exception
   */
  public function startSession($planningId, ?string $notes = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // Validar que el inicio esté dentro del horario laboral (8am a 6pm)
    $this->validateWorkHours('iniciar');

    // Validar que no se pueda iniciar antes de la hora programada (permitiendo 0 minutos de antelación)
    if ($planning->planned_start_datetime) {
      $now = now();
      $allowedStartTime = $planning->planned_start_datetime->copy()->subMinutes(0);

      if ($now->lt($allowedStartTime)) {
        $plannedTime = $planning->planned_start_datetime->format('d/m/Y h:i A');
        $earliestTime = $allowedStartTime->format('h:i A');
        throw new Exception("No puede iniciar el trabajo antes de la hora programada. El trabajo está programado para {$plannedTime}. Puede iniciarlo desde las {$earliestTime}.");
      }
    }

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

    // Verificar si ya hay una sesión activa
    if ($planning->activeSession()) {
      throw new Exception('Ya existe una sesión activa. Debe pausarla o completarla antes de iniciar una nueva.');
    }

    // Validar que no tenga otro trabajo en progreso (evitar trabajos en paralelo)
    $otherActiveWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
      ->where('id', '!=', $planning->id)
      ->whereHas('sessions', function ($query) {
        $query->where('status', 'in_progress')->whereNull('end_datetime');
      })
      ->first();

    if ($otherActiveWork) {
      throw new Exception('No puede iniciar este trabajo porque ya tiene otro trabajo en progreso. Debe completar o pausar el trabajo actual primero.');
    }

    // Validar que no haya pasado más de la mitad del tiempo asignado
    if ($planning->planned_start_datetime && $planning->planned_end_datetime) {
      $now = now();
      $plannedStart = $planning->planned_start_datetime;
      $plannedEnd = $planning->planned_end_datetime;

      // Calcular la duración total en minutos
      $totalDurationMinutes = $plannedStart->diffInMinutes($plannedEnd);

      // Calcular la mitad de la duración
      $halfDurationMinutes = $totalDurationMinutes / 2;

      // Calcular el límite de tiempo (hora de inicio + mitad de duración)
      $timeLimit = $plannedStart->copy()->addMinutes($halfDurationMinutes);

      // Si la hora actual es mayor al límite, lanzar error
      if ($now->gt($timeLimit)) {
        throw new Exception('No se puede iniciar el trabajo porque ya pasó más de la mitad del tiempo asignado. Debe reprogramar este trabajo.');
      }
    }

    // Validar que no se adelante trabajos del mismo día (debe ir en orden cronológico)
    // Solo considera trabajos PENDIENTES (ignora los pausados)
    if ($planning->planned_start_datetime) {
      $currentPlannedStart = $planning->planned_start_datetime;
      $dayStart = $currentPlannedStart->copy()->startOfDay();
      $dayEnd = $currentPlannedStart->copy()->endOfDay();

      // Buscar trabajos PENDIENTES anteriores del mismo día (ignora pausados)
      $previousPendingWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
        ->where('id', '!=', $planning->id)
        ->where('status', 'planned')
        ->whereBetween('planned_start_datetime', [$dayStart, $dayEnd])
        ->where('planned_start_datetime', '<', $currentPlannedStart)
        ->orderBy('planned_start_datetime', 'asc')
        ->first();

      if ($previousPendingWork) {
        $previousStartTime = $previousPendingWork->planned_start_datetime->format('h:i A');
        $currentStartTime = $currentPlannedStart->format('h:i A');
        throw new Exception("No puede iniciar el trabajo de las {$currentStartTime} porque tiene un trabajo anterior programado para las {$previousStartTime} que aún no ha iniciado. Debe completar los trabajos en orden cronológico.");
      }
    }

    $planning->startSession($notes);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Pausa el trabajo actual
   * @throws Exception
   */
  public function pauseSession($planningId, ?string $pauseReason = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // Validar que el inicio esté dentro del horario laboral (8am a 6pm)
    $this->validateWorkHours('pausar');

    if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
      throw new Exception('No se puede pausar una sesión de un trabajo con fecha planificada pasada');
    }

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
   * @throws Exception
   */
  public function continueSession($planningId, ?string $notes = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    // Validar que el inicio esté dentro del horario laboral (8am a 6pm)
    $this->validateWorkHours('continuar');

    // Validar que sea un trabajo del día actual (trabajos de días anteriores usan startSession)
    if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
      throw new Exception('No se puede continuar un trabajo de días anteriores. Los trabajos pausados de días pasados se reinician como nuevas sesiones.');
    }

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

    // Validar que no tenga otro trabajo en progreso (evitar trabajos en paralelo)
    $otherActiveWork = ApWorkOrderPlanning::where('worker_id', $planning->worker_id)
      ->where('id', '!=', $planning->id)
      ->whereHas('sessions', function ($query) {
        $query->where('status', 'in_progress')->whereNull('end_datetime');
      })
      ->first();

    if ($otherActiveWork) {
      throw new Exception('No puede continuar este trabajo porque ya tiene otro trabajo en progreso. Debe completar o pausar el trabajo actual primero.');
    }

    $planning->continueSession($notes);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Completa el trabajo
   * @throws Exception
   */
  public function completeWork($planningId)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    // Validar que el inicio esté dentro del horario laboral (8am a 6pm)
    $this->validateWorkHours('completar');

    if ($planning->planned_start_datetime && $planning->planned_start_datetime->startOfDay()->lt(now()->startOfDay())) {
      throw new Exception('No se puede completar un trabajo con fecha planificada pasada');
    }

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
   */
  private function validateWorkHours(string $event): void
  {
    // Validar que el inicio esté dentro del horario laboral (8am a 6pm)
    $now = now();
    $workStartTime = $now->copy()->setTimeFromTimeString(ApWorkOrderPlanning::WORK_START_TIME);
    $workEndTime = $now->copy()->setTimeFromTimeString(ApWorkOrderPlanning::WORK_END_TIME);

    if ($now->lessThan($workStartTime)) {
      throw new Exception(
        'No puede ' . $event . ' el trabajo antes de la hora de entrada (' . ApWorkOrderPlanning::WORK_START_TIME . '). ' .
        'Hora actual: ' . $now->format('H:i')
      );
    }

    if ($now->greaterThan($workEndTime)) {
      throw new Exception(
        'No puede ' . $event . ' el trabajo después de la hora de salida (' . ApWorkOrderPlanning::WORK_END_TIME . '). ' .
        'Hora actual: ' . $now->format('H:i')
      );
    }
  }
}
