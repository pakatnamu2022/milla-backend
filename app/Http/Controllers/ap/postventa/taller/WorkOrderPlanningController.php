<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\CancelWorkOrderPlanningRequest;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderPlanningRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderPlanningRequest;
use App\Http\Requests\ap\postventa\taller\SupervisorCompleteWorkOrderPlanningRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderPlanningRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderPlanningService;

class WorkOrderPlanningController extends Controller
{
  protected WorkOrderPlanningService $service;

  public function __construct(WorkOrderPlanningService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderPlanningRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderPlanningRequest $request)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->store($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateWorkOrderPlanningRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function consolidated($workOrderId)
  {
    try {
      return $this->success($this->service->consolidated($workOrderId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener lista consolidada de trabajadores únicos que participaron en una orden de trabajo
   * GET /api/work-order-planning/workers/{work_order_id}
   */
  public function getWorkers($workOrderId)
  {
    try {
      return $this->success($this->service->getWorkers($workOrderId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Permite al supervisor finalizar manualmente un trabajo cuando el trabajador olvida hacerlo
   * POST /api/workOrderPlanning/{id}/supervisor-complete
   */
  public function supervisorComplete(SupervisorCompleteWorkOrderPlanningRequest $request, $id)
  {
    try {
      return $this->success($this->service->supervisorComplete($id, $request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Permite al supervisor crear y finalizar automáticamente un trabajo que el técnico
   * nunca inició, respetando el horario planificado (inicio y fin asignados)
   * POST /api/workOrderPlanning/{id}/auto-complete
   */
  public function autoComplete($id)
  {
    try {
      return $this->success($this->service->autoComplete($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Cancela un trabajo de planificación
   * POST /api/workOrderPlanning/{id}/cancel
   */
  public function cancel(CancelWorkOrderPlanningRequest $request, $id)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->cancel($id, $data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
