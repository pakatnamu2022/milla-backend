<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexVehicleVNRequest;
use App\Http\Requests\ap\comercial\StoreVehicleVNRequest;
use App\Http\Requests\ap\comercial\UpdateVehicleVNRequest;
use App\Http\Services\ap\comercial\VehicleVNService;
use App\Models\ap\comercial\VehicleVN;
use Illuminate\Http\Request;

class VehicleVNController extends Controller
{
  protected VehicleVNService $service;

  public function __construct(VehicleVNService $service)
  {
    $this->service = $service;
  }

  public function index(IndexVehicleVNRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreVehicleVNRequest $request)
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

  public function update(UpdateVehicleVNRequest $request, $id)
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
