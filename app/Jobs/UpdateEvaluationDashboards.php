<?php

namespace App\Jobs;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateEvaluationDashboards implements ShouldQueue
{
  use Queueable;

  protected $evaluationId;

  /**
   * Create a new job instance.
   */
  public function __construct($evaluationId = null)
  {
    $this->evaluationId = $evaluationId;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $query = Evaluation::query();

    if ($this->evaluationId) {
      $query->where('id', $this->evaluationId);
    }

    $evaluations = $query->get();

    foreach ($evaluations as $evaluation) {
      $this->updateEvaluationDashboard($evaluation);
      $this->updatePersonDashboards($evaluation);
    }
  }

  /**
   * Actualiza el dashboard de la evaluación
   */
  protected function updateEvaluationDashboard(Evaluation $evaluation)
  {
    // Obtener datos originales del modelo
    $progressStats = $evaluation->progress_stats_fallback;
    $competenceStats = $evaluation->competence_stats;
    $evaluatorTypeStats = $evaluation->evaluator_type_stats;
    $participantRanking = $evaluation->participant_ranking;
    $executiveSummary = $evaluation->executive_summary;
    $resultsStats = $evaluation->fallbackCalculateResultsStats();

    // Actualizar o crear el dashboard
    EvaluationDashboard::updateOrCreate(
      ['evaluation_id' => $evaluation->id],
      [
        'total_participants' => $progressStats['total_participants'],
        'completed_participants' => $progressStats['completed_participants'],
        'in_progress_participants' => $progressStats['in_progress_participants'],
        'not_started_participants' => $progressStats['not_started_participants'],
        'completion_percentage' => $progressStats['completion_percentage'],
        'progress_percentage' => $progressStats['progress_percentage'],
        'average_final_score' => $executiveSummary['average_final_score'] ?? 0,
        'performance_percentage' => $executiveSummary['performance_percentage'] ?? 0,
        'competence_stats' => $competenceStats,
        'evaluator_type_stats' => $evaluatorTypeStats,
        'participant_ranking' => $participantRanking,
        'executive_summary' => $executiveSummary,
        'results_stats' => $resultsStats,
        'last_calculated_at' => Carbon::now(),
      ]
    );
  }

  /**
   * Actualiza los dashboards de las personas
   */
  protected function updatePersonDashboards(Evaluation $evaluation)
  {
    $personResults = $evaluation->personResults()->get();

    foreach ($personResults as $personResult) {
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
    }

    // Eliminar dashboards de personas que ya no están en la evaluación
    EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)
      ->whereNotIn('person_id', $personResults->pluck('person_id'))
      ->delete();
  }
}
