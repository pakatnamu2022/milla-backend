<?php

namespace App\Models\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Illuminate\Database\Eloquent\Model;

class EvaluationDashboard extends Model
{
  protected $table = 'evaluation_dashboards';

  protected $fillable = [
    'evaluation_id',
    'total_participants',
    'completed_participants',
    'in_progress_participants',
    'not_started_participants',
    'completion_percentage',
    'progress_percentage',
    'average_final_score',
    'performance_percentage',
    'competence_stats',
    'evaluator_type_stats',
    'participant_ranking',
    'executive_summary',
    'results_stats',
    'last_calculated_at',
  ];

  protected $casts = [
    'completion_percentage' => 'decimal:2',
    'progress_percentage' => 'decimal:2',
    'average_final_score' => 'decimal:2',
    'performance_percentage' => 'decimal:2',
    'competence_stats' => 'json',
    'evaluator_type_stats' => 'json',
    'participant_ranking' => 'json',
    'executive_summary' => 'json',
    'results_stats' => 'json',
    'last_calculated_at' => 'datetime',
  ];

  public function evaluation()
  {
    return $this->belongsTo(Evaluation::class, 'evaluation_id');
  }

  public function resetStats()
  {
    $this->update([
      'total_participants' => 0,
      'completed_participants' => 0,
      'in_progress_participants' => 0,
      'not_started_participants' => 0,
      'completion_percentage' => 0.00,
      'progress_percentage' => 0.00,
      'average_final_score' => 0.00,
      'performance_percentage' => 0.00,
      'competence_stats' => null,
      'evaluator_type_stats' => null,
      'participant_ranking' => null,
      'executive_summary' => null,
      'results_stats' => null,
      'last_calculated_at' => null,
    ]);
  }
}
