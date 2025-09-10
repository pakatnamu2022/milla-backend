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

  public function indexRecord(IndexApAssignCompanyBranchRequest $request)
  {
    try {
      return $this->service->listRecord($request);
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

  public function update(UpdateApAssignCompanyBranchRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['company_branch_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
