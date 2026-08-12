<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktBudgetRequest;
use App\Http\Requests\ap\marketing\StoreMktBudgetFundingRequest;
use App\Http\Requests\ap\marketing\StoreMktBudgetRequest;
use App\Http\Requests\ap\marketing\UpdateMktBudgetRequest;
use App\Http\Services\ap\marketing\MktBudgetService;

class MktBudgetController extends Controller
{
  protected MktBudgetService $service;

  public function __construct(MktBudgetService $service)
  {
    $this->service = $service;
  }

  public function index(IndexMktBudgetRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktBudgetRequest $request)
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

  public function update(UpdateMktBudgetRequest $request, $id)
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

  public function addFunding(StoreMktBudgetFundingRequest $request, $id)
  {
    try {
      return $this->success($this->service->addFunding($id, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
