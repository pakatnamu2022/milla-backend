<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApSupplierOrderTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApSupplierOrderTypeRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApSupplierOrderTypeRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApSupplierOrderTypeService;
use App\Models\ap\configuracionComercial\vehiculo\ApSupplierOrderType;
use Illuminate\Http\Request;

class ApSupplierOrderTypeController extends Controller
{
  protected ApSupplierOrderTypeService $service;

  public function __construct(ApSupplierOrderTypeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApSupplierOrderTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApSupplierOrderTypeRequest $request)
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

  public function update(UpdateApSupplierOrderTypeRequest $request, $id)
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
