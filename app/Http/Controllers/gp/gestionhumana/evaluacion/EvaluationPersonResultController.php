<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonResultRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonResultRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonResultRequest;
use App\Http\Requests\PersonEvaluationRequest;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
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

  public function getEvaluationsByPersonToEvaluate(Request $request, int $id)
  {
    try {
      return $this->service->getEvaluationsByPersonToEvaluate($request, $id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Dashboard del Líder - Vista consolidada del equipo
   * GET /api/gp/gh/performanceEvaluation/leader-dashboard/{evaluation_id}
   */
  public function getLeaderDashboard(Request $request, int $evaluation_id)
  {
    try {
      return $this->success($this->service->getLeaderDashboard($request, $evaluation_id));
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

  public function regenerate(int $personId, int $evaluationId)
  {
    try {
      return $this->success($this->service->regeneratePersonEvaluation($personId, $evaluationId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getBossesByEvaluation(int $evaluationId)
  {
    try {
      $bosses = $this->service->getBossesByEvaluation($evaluationId);
      return $this->success(WorkerResource::collection($bosses));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
