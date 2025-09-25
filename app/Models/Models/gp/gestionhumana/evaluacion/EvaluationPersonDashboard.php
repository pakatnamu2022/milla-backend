<?php

namespace App\Models\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;

class EvaluationPersonDashboard extends Model
{
    protected $table = 'evaluation_person_dashboards';

    protected $fillable = [
        'evaluation_id',
        'person_id',
        'completion_rate',
        'completed_sections',
        'total_sections',
        'is_completed',
        'objectives_completion_rate',
        'objectives_completed',
        'objectives_total',
        'objectives_is_completed',
        'has_objectives',
        'competences_completion_rate',
        'competences_completed',
        'competences_total',
        'competences_is_completed',
        'competence_groups',
        'progress_status',
        'grouped_competences',
        'total_progress_detail',
        'objectives_progress_detail',
        'competences_progress_detail',
        'last_calculated_at',
    ];

    protected $casts = [
        'completion_rate' => 'decimal:2',
        'is_completed' => 'boolean',
        'objectives_completion_rate' => 'decimal:2',
        'objectives_is_completed' => 'boolean',
        'has_objectives' => 'boolean',
        'competences_completion_rate' => 'decimal:2',
        'competences_is_completed' => 'boolean',
        'grouped_competences' => 'json',
        'total_progress_detail' => 'json',
        'objectives_progress_detail' => 'json',
        'competences_progress_detail' => 'json',
        'last_calculated_at' => 'datetime',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
