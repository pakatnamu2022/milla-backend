<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonService;
use Illuminate\Http\Request;

class EvaluationPersonController extends Controller
{
  protected EvaluationPersonService $service;

  public function __construct(EvaluationPersonService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationPersonRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
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

  public function update(UpdateEvaluationPersonRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function testUpdateAllResultsWithGoals(int $id)
  {
    try {
      return $this->success($this->service->testUpdateAllResultsWithGoals($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(Request $request, int $id)
  {
    try {
      $alsoRemoveFromCycle = filter_var($request->query('also_remove_from_cycle', false), FILTER_VALIDATE_BOOLEAN);
      return $this->service->destroy($id, $alsoRemoveFromCycle);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Recalcular resultados para todas las personas de una evaluación
   */
  public function recalculateAllResults(int $evaluationId)
  {
    try {
      $result = $this->service->recalculateAllResults($evaluationId);
      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener estadísticas de una evaluación
   */
  public function getEvaluationStats(int $evaluationId)
  {
    try {
      $stats = $this->service->getEvaluationStats($evaluationId);
      return $this->success($stats);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
