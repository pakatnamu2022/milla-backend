<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonResultRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonResultRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonResultRequest;
use App\Http\Requests\PersonEvaluationRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonResultService;
use Illuminate\Http\Request;

class EvaluationPersonResultController extends Controller
{
  protected EvaluationPersonResultService $service;

  public function __construct(EvaluationPersonResultService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationPersonResultRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getTeamByChief(Request $request, int $chief_id)
  {
    try {
      return $this->service->getTeamByChief($request, $chief_id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getByPersonAndEvaluation(PersonEvaluationRequest $request)
  {
    try {
      return $this->service->getByPersonAndEvaluation($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonResultRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeMany($id)
  {
    try {
      return $this->success($this->service->storeMany($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateEvaluationPersonResultRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
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

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
