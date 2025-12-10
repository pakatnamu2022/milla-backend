<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderService;

class WorkOrderController extends Controller
{
  protected WorkOrderService $service;

  public function __construct(WorkOrderService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderRequest $request)
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

  public function update(UpdateWorkOrderRequest $request, $id)
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

  public function calculateTotals($id)
  {
    try {
      return $this->success($this->service->calculateTotals($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
