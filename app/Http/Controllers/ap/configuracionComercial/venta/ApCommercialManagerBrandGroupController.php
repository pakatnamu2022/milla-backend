<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApCommercialManagerBrandGroupRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApCommercialManagerBrandGroupRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupService;
use App\Models\ap\configuracionComercial\venta\ApCommercialManagerBrandGroup;
use Illuminate\Http\Request;

class ApCommercialManagerBrandGroupController extends Controller
{
  protected ApCommercialManagerBrandGroupService $service;

  public function __construct(ApCommercialManagerBrandGroupService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApCommercialManagerBrandGroupRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function indexRecord(IndexApCommercialManagerBrandGroupRequest $request)
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

  public function update(UpdateApCommercialManagerBrandGroupRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['brand_group_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
