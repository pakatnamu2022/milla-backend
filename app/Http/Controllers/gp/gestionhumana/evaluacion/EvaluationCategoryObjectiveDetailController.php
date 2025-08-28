<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCategoryObjectiveDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationCategoryObjectiveDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationCategoryObjectiveDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailService;

class EvaluationCategoryObjectiveDetailController extends Controller
{
  protected EvaluationCategoryObjectiveDetailService $service;

  public function __construct(EvaluationCategoryObjectiveDetailService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationCategoryObjectiveDetailRequest $request)
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

  public function store(StoreEvaluationCategoryObjectiveDetailRequest $request)
  {
    try {
      return $this->service->store($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id)
  {
    //
  }

  public function update(UpdateEvaluationCategoryObjectiveDetailRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->service->update($data);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
