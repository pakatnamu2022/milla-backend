<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexPhoneLineWorkerRequest;
use App\Http\Requests\gp\tics\StorePhoneLineWorkerRequest;
use App\Http\Requests\gp\tics\UpdatePhoneLineWorkerRequest;
use App\Http\Services\gp\tics\PhoneLineWorkerService;
use Throwable;

class PhoneLineWorkerController extends Controller
{
  protected PhoneLineWorkerService $service;

  public function __construct(PhoneLineWorkerService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPhoneLineWorkerRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function history($phoneLineId)
  {
    try {
      return $this->success($this->service->history($phoneLineId));
    } catch (Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(StorePhoneLineWorkerRequest $request)
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

  public function update(UpdatePhoneLineWorkerRequest $request, $id)
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
}
