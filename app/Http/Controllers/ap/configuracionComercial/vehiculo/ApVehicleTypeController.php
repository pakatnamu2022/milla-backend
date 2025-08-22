<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApVehicleTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApVehicleTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApVehicleTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApVehicleTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleType;
use Illuminate\Http\Request;

class ApVehicleTypeController extends Controller
{
  protected ApVehicleTypeService $service;

  public function __construct(ApVehicleTypeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleTypeRequest $request)
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

  public function update(UpdateApVehicleTypeRequest $request, $id)
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
