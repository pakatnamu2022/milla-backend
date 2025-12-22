<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderPlanningRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderPlanningRequest;
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
      return $this->success($this->service->store($request->all()));
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
      $data = $request->all();
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
}