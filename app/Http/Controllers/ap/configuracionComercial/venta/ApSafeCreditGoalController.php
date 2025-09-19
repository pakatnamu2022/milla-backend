<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApSafeCreditGoalRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApSafeCreditGoalRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApSafeCreditGoalRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApSafeCreditGoalService;
use App\Models\ap\configuracionComercial\venta\ApSafeCreditGoal;
use Illuminate\Http\Request;

class ApSafeCreditGoalController extends Controller
{
  protected ApSafeCreditGoalService $service;

  public function __construct(ApSafeCreditGoalService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApSafeCreditGoalRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApSafeCreditGoalRequest $request)
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

  public function update(UpdateApSafeCreditGoalRequest $request, $id)
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
