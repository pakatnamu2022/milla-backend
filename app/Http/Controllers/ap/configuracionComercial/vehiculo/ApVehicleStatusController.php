<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApVehicleStatusRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApVehicleStatusRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApVehicleStatusRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApVehicleStatusService;
use Illuminate\Http\Request;

class ApVehicleStatusController extends Controller
{
  protected ApVehicleStatusService $service;

  public function __construct(ApVehicleStatusService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApVehicleStatusRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApVehicleStatusRequest $request)
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

  public function update(UpdateApVehicleStatusRequest $request, $id)
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
