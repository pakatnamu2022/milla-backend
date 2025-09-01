<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApAssignSedeRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApAssignSedeRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApAssignSedeRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApAssignSedeService;
use Illuminate\Http\Request;

class ApAssignSedeController extends Controller
{
  protected ApAssignSedeService $service;

  public function __construct(ApAssignSedeService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApAssignSedeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApAssignSedeRequest $request)
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

  public function update(UpdateApAssignSedeRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['sede_id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
