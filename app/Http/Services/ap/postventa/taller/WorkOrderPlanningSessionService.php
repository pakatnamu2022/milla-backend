<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningResource;
use App\Http\Resources\ap\postventa\taller\WorkOrderPlanningSessionResource;
use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use App\Models\ap\postventa\taller\ApWorkOrderPlanningSession;
use Exception;

class WorkOrderPlanningSessionService
{
  /**
   * Inicia una nueva sesión de trabajo
   */
  public function startSession($planningId, ?string $notes = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    // Verificar si ya hay una sesión activa
    if ($planning->activeSession()) {
      throw new Exception('Ya existe una sesión activa. Debe pausarla o completarla antes de iniciar una nueva.');
    }

    $session = $planning->startSession($notes);

    return new WorkOrderPlanningResource($planning->fresh(['worker', 'workOrder', 'sessions']));
  }

  /**
   * Pausa el trabajo actual
   */
  public function pauseSession($planningId, ?string $pauseReason = null)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

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
   * Completa el trabajo
   */
  public function completeWork($planningId)
  {
    $planning = ApWorkOrderPlanning::with(['worker', 'workOrder'])->find($planningId);

    if (!$planning) {
      throw new Exception('Planificación no encontrada');
    }

    if ($planning->status === 'completed') {
      throw new Exception('El trabajo ya está completado');
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
}