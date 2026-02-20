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

  public function index(IndexEvaluationPersonCycleDetailRequest $request, int $id)
  {
    try {
      return $this->service->list($request, $id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonCycleDetailRequest $request)
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

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

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

  public function regenerateWeights(int $cycle)
  {
    try {
      return $this->success($this->service->regenerateWeightsByCycle($cycle));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Previsualiza los workers elegibles para un ciclo (no asignados aún).
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
   * Inserta múltiples workers en un ciclo.
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
