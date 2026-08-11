<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexObjectiveSedePeriodPvRequest;
use App\Http\Requests\ap\postventa\taller\StoreObjectiveSedePeriodPvRequest;
use App\Http\Requests\ap\postventa\taller\UpdateObjectiveSedePeriodPvRequest;
use App\Http\Services\ap\postventa\taller\ObjectiveSedePeriodPvService;

class ObjectiveSedePeriodPvController extends Controller
{
  protected ObjectiveSedePeriodPvService $service;

  public function __construct(ObjectiveSedePeriodPvService $service)
  {
    $this->service = $service;
  }

  public function index(IndexObjectiveSedePeriodPvRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreObjectiveSedePeriodPvRequest $request)
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

  public function update(UpdateObjectiveSedePeriodPvRequest $request, $id)
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
