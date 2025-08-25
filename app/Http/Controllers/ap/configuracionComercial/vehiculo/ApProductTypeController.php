<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApProductTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApProductTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApProductTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApProductTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApProductType;
use Illuminate\Http\Request;

class ApProductTypeController extends Controller

{
  protected ApProductTypeService $service;

  public function __construct(ApProductTypeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApProductTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApProductTypeRequest $request)
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

  public function update(UpdateApProductTypeRequest $request, $id)
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
