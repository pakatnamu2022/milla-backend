<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevelopmentPlanObjectiveCompetence extends Model
{
  use SoftDeletes;

  protected $table = 'development_plan_objective_competence';

  protected $fillable = [
    'development_plan_id',
    'objective_detail_id',
    'competence_detail_id',
  ];

  // Relationships
  public function developmentPlan(): BelongsTo
  {
    return $this->belongsTo(DetailedDevelopmentPlan::class, 'development_plan_id');
  }

  public function objectiveDetail(): BelongsTo
  {
    return $this->belongsTo(EvaluationPersonCycleDetail::class, 'objective_detail_id');
  }

  public function competenceDetail(): BelongsTo
  {
    return $this->belongsTo(EvaluationPersonCompetenceDetail::class, 'competence_detail_id');
  }
}
