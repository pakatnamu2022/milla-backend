<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApGearShiftTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApGearShiftTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApGearShiftTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApGearShiftTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApGearShiftType;
use Illuminate\Http\Request;

class ApGearShiftTypeController extends Controller
{
    protected ApGearShiftTypeService $service;

    public function __construct(ApGearShiftTypeService $service)
    {
        $this->service = $service;
    }

  public function index(IndexApGearShiftTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApGearShiftTypeRequest $request)
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

  public function update(UpdateApGearShiftTypeRequest $request, $id)
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
