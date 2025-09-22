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
use function dd;
use function logger;

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

    $ids = EvaluationPerson::where('evaluation_id', $evaluation->id)->pluck('id');
    EvaluationPerson::destroy($ids);

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
    $evaluationPerson = $this->find($data['id']);

    // Si se está actualizando el resultado, calcular cumplimiento y calificación
    if (isset($data['result'])) {
      $result = floatval($data['result']);
      $personCycleDetail = $evaluationPerson->personCycleDetail;

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

    $evaluationPerson->update($data);

    $this->recalculatePersonResults($evaluationPerson->evaluation_id, $evaluationPerson->person_id);
    return new EvaluationPersonResource($evaluationPerson);
  }

  /**
   * Calcular cumplimiento según tipo de objetivo
   */
  private function calculateCompliance($result, $goal, $isAscending)
  {
    if ($goal == 0) {
      if ($isAscending) {
        // Caso problemático: no se puede tener meta ascendente de 0
        // Opciones:
        return $result > 0 ? 100 : 0; // O lanzar excepción
      } else {
        // Para descendentes: meta 0 es válida
        return $result == 0 ? 100 : 0;
      }
    }

    if ($isAscending) {
      return ($result / $goal) * 100;
    } else {
      $adjustedResult = $result == 0 ? 1 : $result;
      return ($goal / $adjustedResult) * 100;
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
    $this->competenceDetailService->updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult);
  }

  /**
   * Calcular resultado de objetivos basado en EvaluationPerson
   */
  public function calculateObjectivesResult($evaluationId, $personId)
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

    foreach ($evaluationPersons as $evaluationPerson) {
      $weight = $evaluationPerson->personCycleDetail->weight ?? 0;
      $qualification = $evaluationPerson->qualification ?? 0;

      $totalWeightedScore += $qualification * ($weight / 100); // Convertir peso a decimal
    }

    return $totalWeightedScore;
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

  /**
   * Función de pruebas: Actualizar todos los resultados de una evaluación
   * asignándoles el valor de su meta correspondiente
   */
  public function testUpdateAllResultsWithGoals($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    // Obtener todas las personas de esta evaluación
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->with('personCycleDetail')
      ->get();

    if ($evaluationPersons->isEmpty()) {
      throw new Exception('No se encontraron personas en esta evaluación');
    }

    DB::beginTransaction();
    try {
      $updatedCount = 0;

      foreach ($evaluationPersons as $evaluationPerson) {
        $personCycleDetail = $evaluationPerson->personCycleDetail;

        if ($personCycleDetail && $personCycleDetail->goal) {
          // Usar el valor de la meta como resultado
          $goal = floatval($personCycleDetail->goal);

          // Actualizar usando el método update existente que ya hace todos los cálculos
          $this->update([
            'id' => $evaluationPerson->id,
            'result' => $goal
          ]);

          $updatedCount++;

          logger("Evaluación de prueba actualizada - Persona ID: {$evaluationPerson->person_id}, Meta/Resultado: {$goal}");
        }
      }

      DB::commit();

      return [
        'message' => 'Prueba completada exitosamente',
        'evaluation_id' => $evaluationId,
        'total_persons' => $evaluationPersons->count(),
        'updated_persons' => $updatedCount,
        'description' => 'Todos los resultados fueron actualizados con el valor de sus metas correspondientes'
      ];

    } catch (\Exception $e) {
      DB::rollBack();
      logger("Error en prueba de evaluación: " . $e->getMessage());
      throw new Exception("Error al ejecutar la prueba: " . $e->getMessage());
    }
  }

  /**
   * Función de pruebas alternativa: Permite especificar un porcentaje de la meta
   * Por ejemplo: 0.8 = 80% de la meta, 1.2 = 120% de la meta
   */
  public function testUpdateAllResultsWithPercentage($evaluationId, $percentage = 1.0)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->with('personCycleDetail')
      ->get();

    if ($evaluationPersons->isEmpty()) {
      throw new Exception('No se encontraron personas en esta evaluación');
    }

    DB::beginTransaction();
    try {
      $updatedCount = 0;

      foreach ($evaluationPersons as $evaluationPerson) {
        $personCycleDetail = $evaluationPerson->personCycleDetail;

        if ($personCycleDetail && $personCycleDetail->goal) {
          $goal = floatval($personCycleDetail->goal);
          $testResult = $goal * $percentage;

          $this->update([
            'id' => $evaluationPerson->id,
            'result' => $testResult
          ]);

          $updatedCount++;

          logger("Evaluación de prueba actualizada - Persona ID: {$evaluationPerson->person_id}, Meta: {$goal}, Resultado: {$testResult} ({$percentage}%)");
        }
      }

      DB::commit();

      return [
        'message' => 'Prueba con porcentaje completada exitosamente',
        'evaluation_id' => $evaluationId,
        'percentage_used' => $percentage * 100 . '%',
        'total_persons' => $evaluationPersons->count(),
        'updated_persons' => $updatedCount,
        'description' => "Todos los resultados fueron actualizados con el {$percentage}% de sus metas correspondientes"
      ];

    } catch (\Exception $e) {
      DB::rollBack();
      logger("Error en prueba de evaluación con porcentaje: " . $e->getMessage());
      throw new Exception("Error al ejecutar la prueba: " . $e->getMessage());
    }
  }
}
