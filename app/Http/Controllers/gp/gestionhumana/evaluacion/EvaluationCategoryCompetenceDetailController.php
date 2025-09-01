<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCategoryCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationCategoryCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationCategoryCompetenceDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailService;

class EvaluationCategoryCompetenceDetailController extends Controller
{
  protected EvaluationCategoryCompetenceDetailService $service;

  public function __construct(EvaluationCategoryCompetenceDetailService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationCategoryCompetenceDetailRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function workers(int $id)
  {
    try {
      return $this->service->workers($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationCategoryCompetenceDetailRequest $request)
  {
    try {
      return $this->service->store($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateEvaluationCategoryCompetenceDetailRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->service->update($data);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(DeleteEvaluationCategoryCompetenceDetailRequest $request)
  {
    try {
      return $this->success($this->service->destroy($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
