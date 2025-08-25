<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApBodyTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApBodyTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApBodyTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApBodyTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApBodyType;
use Illuminate\Http\Request;

class ApBodyTypeController extends Controller
{
  protected ApBodyTypeService $service;

  public function __construct(ApBodyTypeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApBodyTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApBodyTypeRequest $request)
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

  public function update(UpdateApBodyTypeRequest $request, $id)
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
