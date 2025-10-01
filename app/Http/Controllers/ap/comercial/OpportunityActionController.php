<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexOpportunityActionRequest;
use App\Http\Requests\ap\comercial\StoreOpportunityActionRequest;
use App\Http\Requests\ap\comercial\UpdateOpportunityActionRequest;
use App\Http\Services\ap\comercial\OpportunityActionService;

class OpportunityActionController extends Controller
{
  protected OpportunityActionService $service;

  public function __construct(OpportunityActionService $service)
  {
    $this->service = $service;
  }

  public function index(IndexOpportunityActionRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StoreOpportunityActionRequest $request)
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

  public function update(UpdateOpportunityActionRequest $request, $id)
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
