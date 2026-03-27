<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexTypePlanningWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\StoreTypePlanningWorkOrderRequest;
use App\Http\Requests\ap\postventa\taller\UpdateTypePlanningWorkOrderRequest;
use App\Http\Services\ap\postventa\taller\TypePlanningWorkOrderService;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Http\Request;

class TypePlanningWorkOrderController extends Controller
{
  protected TypePlanningWorkOrderService $service;

  public function __construct(TypePlanningWorkOrderService $service)
  {
    $this->service = $service;
  }

  public function index(IndexTypePlanningWorkOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTypePlanningWorkOrderRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
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

  public function update(UpdateTypePlanningWorkOrderRequest $request, $id)
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
}
