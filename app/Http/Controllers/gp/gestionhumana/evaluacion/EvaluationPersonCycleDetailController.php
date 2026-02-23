<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonCycleDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonCycleDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreManyWorkerToCycleRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonCycleDetailRequest;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailService;

class EvaluationPersonCycleDetailController extends Controller
{
  protected EvaluationPersonCycleDetailService $service;

  public function __construct(EvaluationPersonCycleDetailService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of the resource for a specific evaluation cycle.
   * @param IndexEvaluationPersonCycleDetailRequest $request
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(IndexEvaluationPersonCycleDetailRequest $request, int $id)
  {
    try {
      return $this->service->list($request, $id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created resource in storage.
   * @param StoreEvaluationPersonCycleDetailRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(StoreEvaluationPersonCycleDetailRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified resource.
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified resource in storage.
   * @param UpdateEvaluationPersonCycleDetailRequest $request
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(UpdateEvaluationPersonCycleDetailRequest $request, $id)
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
   * Remove the specified resource from storage.
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene los jefes de un ciclo específico.
   * @param int $cycleId
   * @return \Illuminate\Http\JsonResponse
   */
  public function getChiefsByCycle(int $cycleId)
  {
    try {
      $chiefs = $this->service->getChiefsByCycle($cycleId);
      return $this->success(WorkerResource::collection($chiefs));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Vista previa de los pesos de evaluación para un ciclo específico.
   * @param int $cycle
   * @return \Illuminate\Http\JsonResponse
   */
  public function previewWeights(int $cycle)
  {
    try {
      return $this->success($this->service->previewWeightsByCycle($cycle));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Regenera los pesos de evaluación para un ciclo específico.
   * @param int $cycle
   * @return \Illuminate\Http\JsonResponse
   */
  public function regenerateWeights(int $cycle)
  {
    try {
      return $this->success($this->service->regenerateWeightsByCycle($cycle));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Vista previa de los trabajadores elegibles para un ciclo específico.
   * @param int $cycle
   * @return \Illuminate\Http\JsonResponse
   */
  public function previewEligibleWorkers(int $cycle)
  {
    try {
      $workers = $this->service->previewEligibleWorkers($cycle);
      return $this->success(WorkerResource::collection($workers));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Valida si un trabajador es elegible para ser incluido en un ciclo específico.
   * @param int $cycle
   * @param int $worker
   * @return \Illuminate\Http\JsonResponse
   */
  public function validateWorkerForCycle(int $cycle, int $worker)
  {
    try {
      return $this->success($this->service->validateWorkerForCycle($cycle, $worker));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Asocia múltiples trabajadores a un ciclo específico.
   * @param StoreManyWorkerToCycleRequest $request
   * @param int $cycle
   * @return \Illuminate\Http\JsonResponse
   */
  public function storeManyByWorker(StoreManyWorkerToCycleRequest $request, int $cycle)
  {
    try {
      $result = $this->service->storeManyByWorker($cycle, $request->validated()['worker_ids']);
      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
