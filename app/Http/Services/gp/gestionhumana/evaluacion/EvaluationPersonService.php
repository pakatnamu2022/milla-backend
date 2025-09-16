<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonService extends BaseService
{
  protected EvaluationPersonCompetenceDetailService $competenceDetailService;

  public function __construct(EvaluationPersonCompetenceDetailService $competenceDetailService)
  {
    $this->competenceDetailService = $competenceDetailService;
  }

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
    $data['objective_parameter_id'] = $cycle->parameter_id ?? null;
    $data['period_id'] = $cycle->period_id ?? null;
    return $data;
  }

  public function find($id)
  {
    $evaluationPerson = EvaluationPerson::where('id', $id)->first();
    if (!$evaluationPerson) {
      throw new Exception('Evaluación de persona no encontrada');
    }
    return $evaluationPerson;
  }

  public function store($data)
  {
    DB::beginTransaction();
    try {
      $data = $this->enrichData($data);
      $evaluationPerson = EvaluationPerson::create($data);

      // Recalcular resultados después de crear
      $this->recalculatePersonResults($evaluationPerson->evaluation_id, $evaluationPerson->person_id);

      DB::commit();
      return new EvaluationPersonResource($evaluationPerson);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
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
        EvaluationPerson::create($data);
      }
    });
  }

  public function show($id)
  {
    return new EvaluationPersonResource($this->find($id));
  }

  public function update($data)
  {
    DB::beginTransaction();
    try {
      $evaluationPerson = $this->find($data['id']);
      $evaluationPerson->update($data);

      // Recalcular resultados después de actualizar
      $this->recalculatePersonResults($evaluationPerson->evaluation_id, $evaluationPerson->person_id);

      DB::commit();
      return new EvaluationPersonResource($evaluationPerson);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $evaluationPerson = $this->find($id);
      $evaluationId = $evaluationPerson->evaluation_id;
      $personId = $evaluationPerson->person_id;

      $evaluationPerson->delete();

      // Recalcular resultados después de eliminar
      $this->recalculatePersonResults($evaluationId, $personId);

      DB::commit();
      return response()->json(['message' => 'Evaluación de persona eliminada correctamente']);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Recalcular los resultados de una persona en una evaluación
   */
  private function recalculatePersonResults($evaluationId, $personId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    // Calcular resultado de objetivos
    $objectivesResult = $this->calculateObjectivesResult($evaluationId, $personId);

    // Calcular resultado de competencias
    $competencesResult = $this->calculateCompetencesResult($evaluationId, $personId, $evaluation->typeEvaluation);

    // Actualizar EvaluationPersonResult
    $this->updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult);
  }

  /**
   * Calcular resultado de objetivos basado en EvaluationPerson
   */
  private function calculateObjectivesResult($evaluationId, $personId)
  {
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->with('personCycleDetail')
      ->get();

    if ($evaluationPersons->isEmpty()) {
      return 0;
    }

    // Calcular promedio ponderado por peso de cada objetivo
    $totalWeightedScore = 0;
    $totalWeight = 0;

    foreach ($evaluationPersons as $evaluationPerson) {
      $weight = $evaluationPerson->personCycleDetail->weight ?? 0;
      $result = $evaluationPerson->result ?? 0;

      $totalWeightedScore += $result * ($weight / 100); // Convertir peso a decimal
      $totalWeight += ($weight / 100);
    }

    return $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
  }

  /**
   * Calcular resultado de competencias
   */
  private function calculateCompetencesResult($evaluationId, $personId, $evaluationType)
  {
    // Delegar el cálculo al servicio de competencias
    return $this->competenceDetailService->calculateCompetencesResult($evaluationId, $personId, $evaluationType);
  }

  /**
   * Actualizar EvaluationPersonResult
   */
  private function updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult)
  {
    $personResult = EvaluationPersonResult::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->first();

    if ($personResult) {
      // Calcular resultado final basado en porcentajes de la evaluación
      $evaluation = Evaluation::find($evaluationId);
      $competencesPercentage = $evaluation->competencesPercentage / 100;
      $objectivesPercentage = $evaluation->objectivesPercentage / 100;

      $finalResult = ($competencesResult * $competencesPercentage) + ($objectivesResult * $objectivesPercentage);

      $personResult->update([
        'competencesResult' => round($competencesResult, 2),
        'objectivesResult' => round($objectivesResult, 2),
        'result' => round($finalResult, 2)
      ]);
    }
  }

  /**
   * Recalcular resultados para todas las personas de una evaluación
   */
  public function recalculateAllResults($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    // Obtener todas las personas de la evaluación
    $personResults = EvaluationPersonResult::where('evaluation_id', $evaluationId)->get();

    DB::beginTransaction();
    try {
      foreach ($personResults as $personResult) {
        $this->recalculatePersonResults($evaluationId, $personResult->person_id);
      }

      DB::commit();

      return [
        'message' => 'Resultados recalculados exitosamente',
        'evaluation_id' => $evaluationId,
        'persons_updated' => $personResults->count()
      ];

    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Obtener estadísticas de una evaluación
   */
  public function getEvaluationStats($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    $personResults = EvaluationPersonResult::where('evaluation_id', $evaluationId)->get();

    $stats = [
      'total_personas' => $personResults->count(),
      'promedio_competencias' => $personResults->avg('competencesResult'),
      'promedio_objetivos' => $personResults->avg('objectivesResult'),
      'promedio_final' => $personResults->avg('result'),
      'competencias_completadas' => EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
        ->where('result', '>', 0)
        ->count(),
      'objetivos_completados' => EvaluationPerson::where('evaluation_id', $evaluationId)
        ->where('result', '>', 0)
        ->count(),
    ];

    return $stats;
  }
}
