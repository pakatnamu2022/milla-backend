<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApWorkOrderAssignOperatorRequest;
use App\Http\Requests\ap\postventa\taller\StoreApWorkOrderAssignOperatorRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApWorkOrderAssignOperatorRequest;
use App\Http\Services\ap\postventa\taller\ApWorkOrderAssignOperatorService;

class ApWorkOrderAssignOperatorController extends Controller
{
  protected ApWorkOrderAssignOperatorService $service;

  public function __construct(ApWorkOrderAssignOperatorService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApWorkOrderAssignOperatorRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApWorkOrderAssignOperatorRequest $request)
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

  public function update(UpdateApWorkOrderAssignOperatorRequest $request, $id)
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