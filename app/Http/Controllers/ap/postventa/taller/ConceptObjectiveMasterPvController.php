<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexConceptObjectiveMasterPvRequest;
use App\Http\Requests\ap\postventa\taller\UpdateOrCreateConceptObjectiveMasterPvRequest;
use App\Http\Services\ap\postventa\taller\ConceptObjectiveMasterPvService;

class ConceptObjectiveMasterPvController extends Controller
{
  protected ConceptObjectiveMasterPvService $service;

  public function __construct(ConceptObjectiveMasterPvService $service)
  {
    $this->service = $service;
  }

  public function index(IndexConceptObjectiveMasterPvRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function updateOrCreate(UpdateOrCreateConceptObjectiveMasterPvRequest $request)
  {
    try {
      return $this->success($this->service->updateOrCreate($request->validated()));
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
