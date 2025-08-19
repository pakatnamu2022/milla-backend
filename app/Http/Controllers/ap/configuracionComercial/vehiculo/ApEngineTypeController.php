<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApEngineTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApEngineTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApEngineTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApEngineTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApEngineType;
use Illuminate\Http\Request;

class ApEngineTypeController extends Controller
{
  protected ApEngineTypeService $service;

  public function __construct(ApEngineTypeService $service)
  {
    $this->service = $service;
  }
  
  public function index(IndexApEngineTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApEngineTypeRequest $request)
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

  public function update(UpdateApEngineTypeRequest $request, $id)
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
