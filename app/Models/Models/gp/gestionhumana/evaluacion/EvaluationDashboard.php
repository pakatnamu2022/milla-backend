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
        'last_calculated_at' => 'datetime',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }
}
