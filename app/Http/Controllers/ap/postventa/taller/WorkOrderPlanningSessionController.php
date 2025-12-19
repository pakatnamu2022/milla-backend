<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\PauseSessionRequest;
use App\Http\Requests\ap\postventa\taller\StartSessionRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderPlanningSessionService;

class WorkOrderPlanningSessionController extends Controller
{
  protected WorkOrderPlanningSessionService $service;

  public function __construct(WorkOrderPlanningSessionService $service)
  {
    $this->service = $service;
  }

  /**
   * Inicia una nueva sesión de trabajo
   * POST /api/work-order-planning/{id}/start
   */
  public function start($planningId, StartSessionRequest $request)
  {
    try {
      $notes = $request->input('notes');
      return $this->success($this->service->startSession($planningId, $notes));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Pausa la sesión actual
   * POST /api/work-order-planning/{id}/pause
   */
  public function pause($planningId, PauseSessionRequest $request)
  {
    try {
      $pauseReason = $request->input('pause_reason');
      return $this->success($this->service->pauseSession($planningId, $pauseReason));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Completa el trabajo
   * POST /api/work-order-planning/{id}/complete
   */
  public function complete($planningId)
  {
    try {
      return $this->success($this->service->completeWork($planningId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene el estado actual de la planificación
   * GET /api/work-order-planning/{id}/status
   */
  public function status($planningId)
  {
    try {
      return $this->success($this->service->getStatus($planningId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Lista todas las sesiones de una planificación
   * GET /api/work-order-planning/{id}/sessions
   */
  public function sessions($planningId)
  {
    try {
      return $this->success($this->service->listSessions($planningId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}