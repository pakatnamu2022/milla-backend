<?php

namespace App\Jobs;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    // Usar métodos optimizados con SQL directo (no calculan sobre cada participante)
    // Pasar false para no disparar el job recursivamente
    $progressStats = $evaluation->fallbackCalculateProgressStats(false);
    $resultsStats = $evaluation->fallbackCalculateResultsStats();

    // Calcular promedio final score de forma eficiente con SQL
    $avgFinalScore = \DB::table('gh_evaluation_person_result')
      ->where('evaluation_id', $evaluation->id)
      ->where('result', '>', 0)
      ->avg('result') ?? 0;

    $maxScore = $evaluation->max_score_final ?? 100;
    $performancePercentage = $maxScore > 0 ? round(($avgFinalScore / $maxScore) * 100, 2) : 0;

    // Actualizar o crear el dashboard con datos básicos
    // Los datos pesados (competence_stats, etc.) se calcularán después con los dashboards individuales
    EvaluationDashboard::updateOrCreate(
      ['evaluation_id' => $evaluation->id],
      [
        'total_participants' => $progressStats['total_participants'],
        'completed_participants' => $progressStats['completed_participants'],
        'in_progress_participants' => $progressStats['in_progress_participants'],
        'not_started_participants' => $progressStats['not_started_participants'],
        'completion_percentage' => $progressStats['completion_percentage'],
        'progress_percentage' => $progressStats['progress_percentage'],
        'average_final_score' => round($avgFinalScore, 2),
        'performance_percentage' => $performancePercentage,
        'results_stats' => $resultsStats,
        'last_calculated_at' => Carbon::now(),
        // Estos campos pesados se actualizarán cuando los chunks terminen
        // 'competence_stats' => null,
        // 'evaluator_type_stats' => null,
        // 'participant_ranking' => null,
        // 'executive_summary' => null,
      ]
    );
  }

  /**
   * Actualiza los dashboards de las personas despachando jobs por chunks
   */
  protected function updatePersonDashboards(Evaluation $evaluation)
  {
    $personIds = $evaluation->personResults()->pluck('person_id')->toArray();

    // Eliminar dashboards de personas que ya no están en la evaluación
    EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)
      ->whereNotIn('person_id', $personIds)
      ->delete();

    // Dividir en chunks de 25 personas y despachar un job por cada chunk
    $chunks = array_chunk($personIds, 25);

    foreach ($chunks as $chunk) {
      UpdateEvaluationPersonDashboardsChunk::dispatch(
        $evaluation->id,
        $chunk
      )->onQueue('evaluation-dashboards');
    }
  }
}
