<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\DeleteManyEvaluationPersonCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\GetByEvaluationEvaluationPersonCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonCompetenceDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonCompetenceDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetailService;

class EvaluationPersonCompetenceDetailController extends Controller
{
  protected EvaluationPersonCompetenceDetailService $service;

  public function __construct(EvaluationPersonCompetenceDetailService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationPersonCompetenceDetailRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonCompetenceDetailRequest $request)
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

  public function update(UpdateEvaluationPersonCompetenceDetailRequest $request, int $id)
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

  /**
   * Get competences by evaluation
   * @param GetByEvaluationEvaluationPersonCompetenceDetailRequest $request
   * @param int $evaluationId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getByEvaluation(GetByEvaluationEvaluationPersonCompetenceDetailRequest $request, int $evaluationId)
  {
    try {
      return $this->service->listByEvaluation($evaluationId, $request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener competencias por evaluación y persona
   */
  public function getByEvaluationAndPerson(int $evaluationId, int $personId)
  {
    try {
      return $this->success($this->service->getByEvaluationAndPerson($evaluationId, $personId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Actualizar múltiples competencias de una persona en una evaluación
   */
  public function updateMany(UpdateEvaluationPersonCompetenceDetailRequest $request)
  {
    try {
      return $this->success($this->service->updateMany($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroyMany(DeleteManyEvaluationPersonCompetenceDetailRequest $request)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->destroyMany($data['ids'], $data['cascade'] ?? false));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Preview de sincronización: muestra qué cambiaría sin aplicar nada.
   * Query param opcional: person_id
   */
  public function previewSync(int $evaluationId, \Illuminate\Http\Request $request)
  {
    try {
      $personId = $request->query('person_id') ? (int) $request->query('person_id') : null;
      return $this->success($this->service->previewSyncCompetences($evaluationId, $personId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Sincroniza competencias con la plantilla CategoryCompetence:
   * elimina las que ya no están en la plantilla y agrega las nuevas.
   * Body JSON opcional: { "person_id": 123 }
   */
  public function syncCompetences(int $evaluationId, \Illuminate\Http\Request $request)
  {
    try {
      $personId = $request->input('person_id') ? (int) $request->input('person_id') : null;
      return $this->success($this->service->syncCompetencesForEvaluation($evaluationId, $personId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
