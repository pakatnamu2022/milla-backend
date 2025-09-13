<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApCommercialManagerBrandGroupRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApCommercialManagerBrandGroupRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApCommercialManagerBrandGroupRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApCommercialManagerBrandGroupService;

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

  public function show($id, IndexApCommercialManagerBrandGroupRequest $request)
  {
    try {
      return $this->success($this->service->show($id, $request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApCommercialManagerBrandGroupRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateApCommercialManagerBrandGroupRequest $request, $id)
  {
    try {
      $data = $request->all();
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
