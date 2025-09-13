<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationService;

class EvaluationController extends Controller
{
  protected EvaluationService $service;

  public function __construct(EvaluationService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationRequest $request)
  {
    try {
      return $this->service->list($request);
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

  /**
   * Obtener estadísticas de competencias de una evaluación
   */
  public function competencesStats(int $id)
  {
    try {
      $evaluation = $this->service->find($id);

      $stats = [
        'total_competencias' => $evaluation->competenceDetails()->count(),
        'competencias_por_tipo' => $evaluation->competenceDetails()
          ->selectRaw('evaluatorType, count(*) as total')
          ->groupBy('evaluatorType')
          ->get()
          ->mapWithKeys(function ($item) {
            $tipos = [
              0 => 'Líder Directo',
              1 => 'Autoevaluación',
              2 => 'Compañeros',
              3 => 'Reportes'
            ];
            return [$tipos[$item->evaluatorType] => $item->total];
          }),
        'personas_con_competencias' => $evaluation->competenceDetails()
          ->distinct('person_id')
          ->count(),
        'promedio_competencias_por_persona' => $evaluation->competenceDetails()
          ->selectRaw('person_id, count(*) as total')
          ->groupBy('person_id')
          ->get()
          ->avg('total')
      ];

      return $this->success($stats);
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
}
