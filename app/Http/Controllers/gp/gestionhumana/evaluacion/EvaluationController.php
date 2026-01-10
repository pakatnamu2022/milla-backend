<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\RegenerateEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonService;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationService;
use App\Jobs\UpdateEvaluationDashboards;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
  protected EvaluationService $service;
  protected EvaluationPersonService $evaluationPersonService;

  public function __construct(EvaluationService $service, EvaluationPersonService $evaluationPersonService)
  {
    $this->service = $service;
    $this->evaluationPersonService = $evaluationPersonService;
  }

  public function index(IndexEvaluationRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function active()
  {
    try {
      return $this->success($this->service->active());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function checkActiveEvaluationByDateRange(CheckEvaluationRequest $request)
  {
    try {
      $data = $request->validated();
      return $this->success($this->service->checkActiveEvaluationByDateRange($data['start_date'], $data['end_date']));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function regenerateEvaluation(RegenerateEvaluationRequest $request, int $id)
  {
    try {
      return $this->success($this->service->regenerateEvaluation($id, $request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function participants(int $id)
  {
    try {
      return $this->success($this->service->participants($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function positions(int $id)
  {
    try {
      return $this->success($this->service->positions($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Crear competencias manualmente para una evaluación 180° o 360°
   */
  public function createCompetences(int $id)
  {
    try {
      $resultado = $this->service->createCompetences($id);
      return response()->json($resultado, $resultado['success'] ? 200 : 400);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }


  public function show(Request $request, int $id)
  {
    try {
      $request->validate([
        'show_extra' => 'sometimes|boolean',
        'recalculate' => 'sometimes|boolean',
      ]);

      $recalculate = $request->input('recalculate', false);

      // Si se solicita recálculo completo
      if ($recalculate) {
        // 1. Recalcular resultados de todas las personas
        $this->evaluationPersonService->recalculateAllResults($id);

        // 2. Disparar actualización completa de dashboards (general + individuales)
        UpdateEvaluationDashboards::dispatch($id, true)
          ->onQueue('evaluation-dashboards');
      }

      $showExtra = $request->input('show_extra', 1);
      return $this->success($this->service->show($showExtra, $id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateEvaluationRequest $request, int $id)
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
