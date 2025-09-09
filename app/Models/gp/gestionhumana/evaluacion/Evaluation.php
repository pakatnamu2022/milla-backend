<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
  protected $table = 'gh_evaluation';

  protected $fillable = [
    'name',
    'start_date',
    'end_date',
    'status',
    'typeEvaluation',
    'objectivesPercentage',
    'competencesPercentage',
    'cycle_id',
    'period_id',
    'competence_parameter_id',
    'objective_parameter_id',
    'final_parameter_id'
  ];

  const filters = [
    'search' => ['name'],
    'name' => 'like',
    'start_date' => '=',
    'end_date' => '=',
    'typeEvaluation' => '=',
    'objectivesPercentage' => '>=',
    'competencesPercentage' => '>=',
    'cycle_id' => '=',
    'period_id' => '=',
    'competence_parameter_id' => '=',
    'objective_parameter_id' => '=',
    'final_parameter_id' => '=',
  ];

  const sorts = [
    'id',
    'name',
    'start_date',
    'end_date',
    'typeEvaluation',
    'objectivesPercentage',
    'competencesPercentage',
    'cycle_id',
    'period_id',
    'competence_parameter_id',
    'objective_parameter_id',
    'final_parameter_id',
    'created_at',
    'updated_at'
  ];

  public function cycle()
  {
    return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
  }

  public function period()
  {
    return $this->belongsTo(EvaluationPeriod::class, 'period_id');
  }

  public function competenceParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'competence_parameter_id');
  }

  public function objectiveParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'objective_parameter_id');
  }

  public function finalParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'final_parameter_id');
  }

}
