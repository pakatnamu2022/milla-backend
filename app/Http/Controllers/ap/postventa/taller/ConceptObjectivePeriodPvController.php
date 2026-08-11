<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexConceptObjectivePeriodPvRequest;
use App\Http\Requests\ap\postventa\taller\StoreConceptObjectivePeriodPvRequest;
use App\Http\Requests\ap\postventa\taller\UpdateConceptObjectivePeriodPvRequest;
use App\Http\Services\ap\postventa\taller\ConceptObjectivePeriodPvService;

class ConceptObjectivePeriodPvController extends Controller
{
  protected ConceptObjectivePeriodPvService $service;

  public function __construct(ConceptObjectivePeriodPvService $service)
  {
    $this->service = $service;
  }

  public function index(IndexConceptObjectivePeriodPvRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreConceptObjectivePeriodPvRequest $request)
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

  public function update(UpdateConceptObjectivePeriodPvRequest $request, $id)
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
