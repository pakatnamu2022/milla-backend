<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktPlanRequest;
use App\Http\Requests\ap\marketing\StoreMktPlanRequest;
use App\Http\Requests\ap\marketing\UpdateMktPlanRequest;
use App\Http\Services\ap\marketing\MktPlanService;

class MktPlanController extends Controller
{
  protected MktPlanService $service;

  public function __construct(MktPlanService $service)
  {
    $this->service = $service;
  }

  public function index(IndexMktPlanRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktPlanRequest $request)
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

  public function update(UpdateMktPlanRequest $request, $id)
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

  public function activate($id)
  {
    try {
      return $this->success($this->service->changeStatus($id, \App\Models\ap\marketing\MktPlan::STATUS_ACTIVE));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function complete($id)
  {
    try {
      return $this->success($this->service->changeStatus($id, \App\Models\ap\marketing\MktPlan::STATUS_CLOSED));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function cancel($id)
  {
    try {
      return $this->success($this->service->changeStatus($id, \App\Models\ap\marketing\MktPlan::STATUS_CANCELLED));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function reportPdf($id)
  {
    try {
      return $this->service->generateFullReportPdf($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
