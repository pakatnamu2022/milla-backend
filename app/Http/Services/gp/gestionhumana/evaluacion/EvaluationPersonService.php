<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResource;
use App\Http\Services\BaseService;
use App\Jobs\UpdateEvaluationDashboards;
use App\Jobs\UpdateEvaluationPersonDashboardsChunk;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    UpdateEvaluationPersonDashboardsChunk::dispatch($evaluationPerson->evaluation_id, [$evaluationPerson->person_id]);

    // Check if all EvaluationPerson records are now completed and trigger full recalculation if so
    $this->checkAndTriggerFullRecalculation($evaluationPerson->evaluation_id);

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
      // Para indicadores descendentes
      if ($result == 0) {
        // Si el resultado es 0, el cumplimiento es máximo (500% cap)
        return 500;
      }
      $compliance = ($goal / $result) * 100;
      // Limitar el compliance máximo para evitar valores fuera de rango
      return min($compliance, 500);
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

    // Primero, recalcular compliance y qualification de cada EvaluationPerson individual
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->with('personCycleDetail')
      ->get();

    foreach ($evaluationPersons as $evaluationPerson) {
      // Solo recalcular si tiene un resultado definido y personCycleDetail
      if ($evaluationPerson->result !== null && $evaluationPerson->personCycleDetail) {
        $result = floatval($evaluationPerson->result);
        $goal = floatval($evaluationPerson->personCycleDetail->goal);
        $isAscending = $evaluationPerson->personCycleDetail->isAscending;

        // Calcular compliance usando la misma lógica que en update()
        $compliance = $this->calculateCompliance($result, $goal, $isAscending);

        // Calcular qualification (limitada a máximo 120%)
        $qualification = min($compliance, 120.00);

        // Actualizar los valores calculados
        $evaluationPerson->update([
          'compliance' => round($compliance, 2),
          'qualification' => round($qualification, 2),
          'wasEvaluated' => true,
        ]);
      }
    }

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
    Evaluation::findOrFail($evaluationId);

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

    // Obtener todas las personas de esta evaluación agrupadas por person_id
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->with('personCycleDetail')
      ->get()
      ->groupBy('person_id');

    if ($evaluationPersons->isEmpty()) {
      throw new Exception('No se encontraron personas en esta evaluación');
    }

    DB::beginTransaction();
    try {
      $totalPersons = $evaluationPersons->count();

      // Calcular distribución: 50% completado, 30% en progreso, 20% sin responder
      $completedCount = intval($totalPersons * 0.5);
      $inProgressCount = intval($totalPersons * 0.3);
      $notAnsweredCount = $totalPersons - $completedCount - $inProgressCount;

      $updatedCount = 0;
      $processedCount = 0;

      foreach ($evaluationPersons as $personId => $personEvaluations) {
        if ($processedCount < $completedCount) {
          // 50% completado - completar TODAS las evaluaciones de esta persona
          foreach ($personEvaluations as $evaluationPerson) {
            $personCycleDetail = $evaluationPerson->personCycleDetail;
            if ($personCycleDetail && $personCycleDetail->goal) {
              $goal = floatval($personCycleDetail->goal);

              $this->update([
                'id' => $evaluationPerson->id,
                'result' => $goal
              ]);

              $updatedCount++;
            }
          }
          $status = 'completado';
        } elseif ($processedCount < $completedCount + $inProgressCount) {
          // 30% en progreso - completar SOLO LA MITAD de las evaluaciones de esta persona
          $evaluationsToComplete = intval($personEvaluations->count() / 2);
          $completedEvaluations = 0;

          foreach ($personEvaluations as $evaluationPerson) {
            $personCycleDetail = $evaluationPerson->personCycleDetail;
            if ($personCycleDetail && $personCycleDetail->goal && $completedEvaluations < $evaluationsToComplete) {
              $goal = floatval($personCycleDetail->goal);

              $this->update([
                'id' => $evaluationPerson->id,
                'result' => $goal
              ]);

              $updatedCount++;
              $completedEvaluations++;
            }
          }
          $status = 'en progreso';
        } else {
          // 20% sin responder - NO ACTUALIZAR NINGUNA evaluación de esta persona
          $status = 'sin responder';
        }

        $processedCount++;
      }

      DB::commit();

      return [
        'message' => 'Prueba completada exitosamente',
        'evaluation_id' => $evaluationId,
        'total_persons' => $totalPersons,
        'total_evaluations_updated' => $updatedCount,
        'distribution' => [
          'personas_completadas' => $completedCount,
          'personas_en_progreso' => $inProgressCount,
          'personas_sin_responder' => $notAnsweredCount
        ],
        'description' => 'Personas distribuidas: 50% completadas (todas sus evaluaciones), 30% en progreso (mitad de sus evaluaciones), 20% sin responder (ninguna evaluación)'
      ];
    } catch (\Exception $e) {
      DB::rollBack();
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
      throw new Exception("Error al ejecutar la prueba: " . $e->getMessage());
    }
  }

  /**
   * Trigger dashboard recalculation with throttling to prevent duplicate dispatches
   * Uses database locking and 1-minute throttling for frequent updates
   */
  private function checkAndTriggerFullRecalculation($evaluationId)
  {
    // Use transaction with locking to prevent duplicates
    return DB::transaction(function () use ($evaluationId) {
      // Lock the dashboard row for update
      $dashboard = EvaluationDashboard::where('evaluation_id', $evaluationId)
        ->lockForUpdate()
        ->first();

      // Check if dashboard update was already queued recently (within 1 minute)
      // This throttling prevents spam when multiple updates happen simultaneously
      $recentlyQueued = $dashboard &&
        $dashboard->full_recalculation_queued_at &&
        $dashboard->full_recalculation_queued_at->gt(now()->subMinute());

      if ($recentlyQueued) {
        return false; // Already queued recently, skip to avoid spam
      }

      // Mark as queued before dispatching
      EvaluationDashboard::updateOrCreate(
        ['evaluation_id' => $evaluationId],
        ['full_recalculation_queued_at' => now()]
      );

      // Dispatch the dashboard update job (only general dashboard, not individual person dashboards)
      UpdateEvaluationDashboards::dispatch($evaluationId, false)
        ->onQueue('evaluation-dashboards');

      return true; // Successfully dispatched
    });
  }
}
