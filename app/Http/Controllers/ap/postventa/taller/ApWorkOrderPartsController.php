<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApWorkOrderPartsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApWorkOrderPartsRequest;
use App\Http\Services\ap\postventa\taller\ApWorkOrderPartsService;

class ApWorkOrderPartsController extends Controller
{
  protected ApWorkOrderPartsService $service;

  public function __construct(ApWorkOrderPartsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApWorkOrderPartsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApWorkOrderPartsRequest $request)
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

  public function update(UpdateApWorkOrderPartsRequest $request, $id)
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

  public function confirmDelivery($id)
  {
    try {
      return $this->success($this->service->confirmDeliveryPart($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
