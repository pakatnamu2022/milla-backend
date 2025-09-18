<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApGoalSellOutInRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApGoalSellOutInRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApGoalSellOutInRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApGoalSellOutInService;

class ApGoalSellOutInController extends Controller
{
  protected ApGoalSellOutInService $service;

  public function __construct(ApGoalSellOutInService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApGoalSellOutInRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApGoalSellOutInRequest $request)
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

  public function update(UpdateApGoalSellOutInRequest $request, $id)
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
