<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderItemRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderItemRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderItemRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderItemService;

class WorkOrderItemController extends Controller
{
  protected WorkOrderItemService $service;

  public function __construct(WorkOrderItemService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderItemRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderItemRequest $request)
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

  public function update(UpdateWorkOrderItemRequest $request, $id)
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
}