<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAccountingAccountPlanRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAccountingAccountPlanRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAccountingAccountPlanRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAccountingAccountPlanService;

class ApAccountingAccountPlanController extends Controller
{
  protected ApAccountingAccountPlanService $service;

  public function __construct(ApAccountingAccountPlanService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApAccountingAccountPlanRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApAccountingAccountPlanRequest $request)
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

  public function update(UpdateApAccountingAccountPlanRequest $request, $id)
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
