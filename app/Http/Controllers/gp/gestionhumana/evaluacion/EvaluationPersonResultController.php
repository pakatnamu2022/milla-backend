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

  /**
   * Display a listing of evaluation person results
   * @param IndexEvaluationPersonResultRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(IndexEvaluationPersonResultRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene las evaluaciones asignadas a una persona para ser evaluada
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
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

  /**
   * Obtiene el resultado de evaluación de una persona para una evaluación específica
   * @param PersonEvaluationRequest $request
   * @return \App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResultResource|\Illuminate\Http\JsonResponse
   */
  public function getByPersonAndEvaluation(PersonEvaluationRequest $request)
  {
    try {
      return $this->service->getByPersonAndEvaluation($request->validated());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Almacena un resultado de evaluación de una persona
   * @param StoreEvaluationPersonResultRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(StoreEvaluationPersonResultRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Almacena múltiples resultados de evaluación de una persona
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function storeMany($id)
  {
    try {
      return $this->success($this->service->storeMany($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Muestra un resultado de evaluación de una persona
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Actualiza un resultado de evaluación de una persona
   * @param UpdateEvaluationPersonResultRequest $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
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

  /**
   * Elimina un resultado de evaluación de una persona
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy(int $id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Exporta los resultados de la evaluación
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Regenera la evaluación de una persona
   * @param int $personId
   * @param int $evaluationId
   * @return \Illuminate\Http\JsonResponse
   */
  public function regenerate(int $personId, int $evaluationId)
  {
    try {
      return $this->success($this->service->regeneratePersonEvaluation($personId, $evaluationId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene todos los jefes asociados a una evaluación
   * @param int $evaluationId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getBossesByEvaluation(int $evaluationId)
  {
    try {
      $bosses = $this->service->getBossesByEvaluation($evaluationId);
      return $this->success(WorkerResource::collection($bosses));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene los líderes con el estado de las evaluaciones que están haciendo a su equipo
   * @param Request $request
   * @param int $evaluationId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getLeadersEvaluationStatus(Request $request, int $evaluationId)
  {
    try {
      return $this->service->getLeadersWithEvaluationStatus($evaluationId, $request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene los miembros del equipo de un líder específico
   * @param Request $request
   * @param int $evaluationId
   * @param int $leaderId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getLeaderTeamMembers(Request $request, int $evaluationId, int $leaderId)
  {
    try {
      return $this->service->getLeaderTeamMembers($evaluationId, $leaderId, $request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
