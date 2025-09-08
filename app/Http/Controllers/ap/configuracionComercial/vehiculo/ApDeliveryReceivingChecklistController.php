<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApDeliveryReceivingChecklistRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApDeliveryReceivingChecklistRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApDeliveryReceivingChecklistRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklistService;
use Illuminate\Http\Request;

class ApDeliveryReceivingChecklistController extends Controller
{
  protected ApDeliveryReceivingChecklistService $service;

  public function __construct(ApDeliveryReceivingChecklistService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApDeliveryReceivingChecklistRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApDeliveryReceivingChecklistRequest $request)
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

  public function update(UpdateApDeliveryReceivingChecklistRequest $request, $id)
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
