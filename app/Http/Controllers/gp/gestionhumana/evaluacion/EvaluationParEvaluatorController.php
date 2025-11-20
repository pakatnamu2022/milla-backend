<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexEvaluationParEvaluatorRequest;
use App\Http\Requests\StoreEvaluationParEvaluatorRequest;
use App\Http\Requests\UpdateEvaluationParEvaluatorRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationParEvaluatorService;

class EvaluationParEvaluatorController extends Controller
{
  protected EvaluationParEvaluatorService $service;

  public function __construct(EvaluationParEvaluatorService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationParEvaluatorRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationParEvaluatorRequest $request)
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

  public function update(UpdateEvaluationParEvaluatorRequest $request, $id)
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
