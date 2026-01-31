<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexEquipmentAssigmentRequest;
use App\Http\Requests\gp\tics\StoreEquipmentAssigmentRequest;
use App\Http\Requests\gp\tics\UpdateEquipmentAssigmentRequest;
use App\Http\Services\gp\tics\EquipmentAssigmentService;
use Throwable;

class EquipmentAssigmentController extends Controller
{
  protected EquipmentAssigmentService $service;

  public function __construct(EquipmentAssigmentService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEquipmentAssigmentRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(StoreEquipmentAssigmentRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateEquipmentAssigmentRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function historyByWorker($personaId)
  {
    try {
      return $this->success($this->service->historyByWorker($personaId));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function historyByEquipment($equipoId)
  {
    try {
      return $this->success($this->service->historyByEquipment($equipoId));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
