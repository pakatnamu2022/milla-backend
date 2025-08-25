<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApVehicleCategoryRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApVehicleCategoryRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApVehicleCategoryRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApVehicleCategoryService;
use Illuminate\Http\Request;

class ApVehicleCategoryController extends Controller
{
  protected ApVehicleCategoryService $service;

  public function __construct(ApVehicleCategoryService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleCategoryRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleCategoryRequest $request)
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

  public function update(UpdateApVehicleCategoryRequest $request, $id)
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
