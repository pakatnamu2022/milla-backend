<?php

namespace App\Jobs;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateEvaluationPersonDashboardsChunk implements ShouldQueue
{
  use Queueable;

  protected $evaluationId;
  protected $personIds;

  /**
   * Número de intentos antes de fallar
   */
  public $tries = 3;

  /**
   * Tiempo máximo de ejecución (segundos)
   */
  public $timeout = 120;

  /**
   * Create a new job instance.
   */
  public function __construct($evaluationId, array $personIds)
  {
    $this->evaluationId = $evaluationId;
    $this->personIds = $personIds;
    $this->onQueue('evaluation-dashboards');
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $evaluation = Evaluation::find($this->evaluationId);

    if (!$evaluation) {
      Log::warning("UpdateEvaluationPersonDashboardsChunk: Evaluación {$this->evaluationId} no encontrada");
      return;
    }

    // Obtener solo los personResults de este chunk con eager loading
    $personResults = EvaluationPersonResult::where('evaluation_id', $this->evaluationId)
      ->whereIn('person_id', $this->personIds)
      ->with([
        'details',
        'competenceDetails',
        'person.position.hierarchicalCategory',
        'person.subordinates',
        'evaluation'
      ])
      ->get();

    foreach ($personResults as $personResult) {
      try {
        $totalProgress = $personResult->total_progress_fallback;
        $objectivesProgress = $personResult->objectives_progress_fallback;
        $competencesProgress = $personResult->competences_progress_fallback;
        $groupedCompetences = $personResult->getGroupedCompetences();

        EvaluationPersonDashboard::updateOrCreate(
          [
            'evaluation_id' => $evaluation->id,
            'person_id' => $personResult->person_id
          ],
          [
            // Total Progress
            'completion_rate' => $totalProgress['completion_rate'],
            'completed_sections' => $totalProgress['completed_sections'],
            'total_sections' => $totalProgress['total_sections'],
            'is_completed' => $totalProgress['is_completed'],

            // Objectives Progress
            'objectives_completion_rate' => $objectivesProgress['completion_rate'],
            'objectives_completed' => $objectivesProgress['completed'],
            'objectives_total' => $objectivesProgress['total'],
            'objectives_is_completed' => $objectivesProgress['is_completed'],
            'has_objectives' => $objectivesProgress['has_objectives'],

            // Competences Progress
            'competences_completion_rate' => $competencesProgress['completion_rate'],
            'competences_completed' => $competencesProgress['completed'],
            'competences_total' => $competencesProgress['total'],
            'competences_is_completed' => $competencesProgress['is_completed'],
            'competence_groups' => $competencesProgress['groups'],

            // Status
            'progress_status' => $personResult->progress_status,

            // JSON Data
            'grouped_competences' => $groupedCompetences,
            'total_progress_detail' => $totalProgress,
            'objectives_progress_detail' => $objectivesProgress,
            'competences_progress_detail' => $competencesProgress,

            'last_calculated_at' => Carbon::now(),
          ]
        );
      } catch (\Exception $e) {
        Log::error("Error actualizando dashboard de persona {$personResult->person_id}: " . $e->getMessage());
        // Continuar con el siguiente participante
      }
    }
  }

  /**
   * Handle a job failure.
   */
  public function failed(\Throwable $exception): void
  {
    Log::error("UpdateEvaluationPersonDashboardsChunk falló para evaluación {$this->evaluationId}", [
      'person_ids' => $this->personIds,
      'error' => $exception->getMessage()
    ]);
  }
}
