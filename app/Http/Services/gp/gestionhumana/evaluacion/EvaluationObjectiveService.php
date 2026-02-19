<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationObjectiveResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class EvaluationObjectiveService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationObjective::class,
      $request,
      EvaluationObjective::filters,
      EvaluationObjective::sorts,
      EvaluationObjectiveResource::class,
    );
  }

  public function find($id)
  {
    $objective = EvaluationObjective::where('id', $id)->first();
    if (!$objective) {
      throw new Exception('Objetivo no encontrado');
    }
    return $objective;
  }

  public function store(array $data)
  {
    $evaluationMetric = EvaluationObjective::create($data);
    return new EvaluationObjectiveResource($evaluationMetric);
  }

  public function show($id)
  {
    return new EvaluationObjectiveResource($this->find($id));
  }

  /**
   * @throws \Throwable
   */
  public function update($data): EvaluationObjectiveResource
  {
    $objective = $this->find($data['id']);
    $objective->update($data);

    /**
     * Update objectives in evaluation_person_cycle_detail of the cycle
     * of the active evaluations that have this objective
     */
    DB::transaction(function () use ($objective) {
      $evaluationService = new EvaluationService();
      $evaluation = $evaluationService->active();
      $cycle = $evaluation->cycle;
      $personCycleDetails = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
        ->where('objective_id', $objective->id)
        ->get();

      foreach ($personCycleDetails as $detail) {
        $detail->objective = $objective->name;
        $detail->isAscending = $objective->isAscending;
        $detail->save();
      }
    });

    return new EvaluationObjectiveResource($objective);
  }

  public function destroy($id)
  {
    $objective = $this->find($id);
    DB::transaction(function () use ($objective) {
      $objective->delete();
    });
    return response()->json(['message' => 'Objetivo eliminado correctamente']);
  }
}
