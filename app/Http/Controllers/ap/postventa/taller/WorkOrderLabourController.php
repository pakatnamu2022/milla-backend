<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexWorkOrderLabourRequest;
use App\Http\Requests\ap\postventa\taller\StoreWorkOrderLabourRequest;
use App\Http\Requests\ap\postventa\taller\UpdateWorkOrderLabourRequest;
use App\Http\Services\ap\postventa\taller\WorkOrderLabourService;

class WorkOrderLabourController extends Controller
{
  protected WorkOrderLabourService $service;

  public function __construct(WorkOrderLabourService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWorkOrderLabourRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWorkOrderLabourRequest $request)
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

  public function update(UpdateWorkOrderLabourRequest $request, $id)
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