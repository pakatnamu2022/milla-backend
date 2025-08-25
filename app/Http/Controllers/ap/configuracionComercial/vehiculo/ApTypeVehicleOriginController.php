<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApTypeVehicleOriginRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApTypeVehicleOriginRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApTypeVehicleOriginRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApTypeVehicleOriginService;
use Illuminate\Http\Request;

class ApTypeVehicleOriginController extends Controller
{
  protected ApTypeVehicleOriginService $service;

  public function __construct(ApTypeVehicleOriginService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApTypeVehicleOriginRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApTypeVehicleOriginRequest $request)
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

  public function update(UpdateApTypeVehicleOriginRequest $request, $id)
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
