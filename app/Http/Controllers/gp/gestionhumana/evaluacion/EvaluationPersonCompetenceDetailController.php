<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
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
}
