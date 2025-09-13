<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAssignCompanyBranchRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAssignCompanyBranchRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAssignCompanyBranchRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAssignCompanyBranchService;

class ApAssignCompanyBranchController extends Controller
{
  protected ApAssignCompanyBranchService $service;

  public function __construct(ApAssignCompanyBranchService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApAssignCompanyBranchRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id, IndexApAssignCompanyBranchRequest $request)
  {
    try {
      return $this->success($this->service->show($id, $request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApAssignCompanyBranchRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApAssignCompanyBranchRequest $request, $id)
  {
    try {
      $data = $request->all();
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
