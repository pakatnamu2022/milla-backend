<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationPerson::class,
      $request,
      EvaluationPerson::filters,
      EvaluationPerson::sorts,
      EvaluationPersonResource::class,
    );
  }

  public function enrichData($data)
  {
    $cycle = EvaluationPersonCycleDetail::find($data['person_cycle_detail_id']);
    $data['objective_parameter_id'] = $cycle->parameter_id;
    $data['period_id'] = $cycle->period_id;
    return $data;
  }

  public function find($id)
  {
    $evaluationCompetence = EvaluationPerson::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    $data = $this->enrichData($data);
    $evaluationMetric = EvaluationPerson::create($data);
    return new EvaluationPersonResource($evaluationMetric);
  }

  public function storeMany($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);
    $details = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)->get();

    EvaluationPerson::where('evaluation_id', $evaluation->id)->delete();

    DB::transaction(function () use ($details, $evaluation) {
      foreach ($details as $detail) {
        $data = [
          'person_id' => $detail->person_id,
          'chief_id' => $detail->chief_id,
          'chief' => $detail->chief,
          'person_cycle_detail_id' => $detail->id,
          'evaluation_id' => $evaluation->id,
          'result' => 0,
          'compliance' => 0,
          'qualification' => 0,
        ];
        EvaluationPerson::create(
          $data
        );
      }
    });
  }

  public function show($id)
  {
    return new EvaluationPersonResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationCompetence = $this->find($data['id']);

    // Si se está actualizando el resultado, calcular cumplimiento y calificación
    if (isset($data['result'])) {
      $result = floatval($data['result']);
      $personCycleDetail = $evaluationCompetence->personCycleDetail;

      if ($personCycleDetail) {
        $goal = floatval($personCycleDetail->goal);
        $isAscending = $personCycleDetail->isAscending;

        // Calcular cumplimiento según si es ascendente o descendente
        $compliance = $this->calculateCompliance($result, $goal, $isAscending);

        // Calcular calificación (limitada a máximo 120%)
        $qualification = min($compliance, 120.00);

        // Agregar los campos calculados a los datos
        $data['compliance'] = round($compliance, 2);
        $data['qualification'] = round($qualification, 2);
        $data['wasEvaluated'] = true;
      }
    }

    $evaluationCompetence->update($data);
    return new EvaluationPersonResource($evaluationCompetence);
  }

  /**
   * Calcular cumplimiento según tipo de objetivo
   */
  private function calculateCompliance($result, $goal, $isAscending)
  {
    if ($goal == 0) {
      return 0;
    }

    if ($isAscending) {
      // Para objetivos ascendentes: mayor resultado = mejor
      // Cumplimiento = (resultado / meta) * 100
      return ($result / $goal) * 100;
    } else {
      // Para objetivos descendentes: menor resultado = mejor
      // Si el resultado es 0, tratarlo como 1 para evitar división infinita
      $adjustedResult = $result == 0 ? 1 : $result;
      return ($goal / $adjustedResult) * 100;
    }
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Evaluación eliminada correctamente']);
  }
}
