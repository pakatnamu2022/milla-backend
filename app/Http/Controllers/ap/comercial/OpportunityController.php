<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexOpportunityRequest;
use App\Http\Requests\ap\comercial\StoreOpportunityRequest;
use App\Http\Requests\ap\comercial\UpdateOpportunityRequest;
use App\Http\Services\ap\comercial\OpportunityService;

class OpportunityController extends Controller
{
  protected OpportunityService $service;

  public function __construct(OpportunityService $service)
  {
    $this->service = $service;
  }

  public function index(IndexOpportunityRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StoreOpportunityRequest $request)
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

  public function update(UpdateOpportunityRequest $request, $id)
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
