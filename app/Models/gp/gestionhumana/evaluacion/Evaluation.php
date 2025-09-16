<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation';

  protected $fillable = [
    'name',
    'start_date',
    'end_date',
    'status',
    'selfEvaluation',        // Agregar este campo
    'partnersEvaluation',    // Agregar este campo
    'typeEvaluation',
    'objectivesPercentage',
    'competencesPercentage',
    'cycle_id',
    'period_id',
    'competence_parameter_id',
    'objective_parameter_id',
    'final_parameter_id'
  ];

  protected $casts = [
    'selfEvaluation' => 'boolean',      // Agregar este cast
    'partnersEvaluation' => 'boolean',  // Agregar este cast
    'objectivesPercentage' => 'decimal:2',
    'competencesPercentage' => 'decimal:2'
  ];

  const filters = [
    'search' => ['name'],
    'name' => 'like',
    'start_date' => '=',
    'end_date' => '=',
    'status' => '=',
    'typeEvaluation' => '=',
    'selfEvaluation' => '=',
    'partnersEvaluation' => '=',
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
    'status',
    'typeEvaluation',
    'selfEvaluation',
    'partnersEvaluation',
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

  public function personResults()
  {
    return $this->hasMany(EvaluationPersonResult::class, 'evaluation_id');
  }

  public function competenceDetails()
  {
    return $this->hasMany(EvaluationPersonCompetenceDetail::class, 'evaluation_id');
  }

  // Métodos auxiliares para obtener texto descriptivo
  public function getTipoEvaluacionTextoAttribute()
  {
    $tipos = [
      0 => 'Objetivos',
      1 => '180°',
      2 => '360°'
    ];

    return $tipos[$this->typeEvaluation] ?? 'Desconocido';
  }

  public function getEstadoTextoAttribute()
  {
    $estados = [
      0 => 'Programada',
      1 => 'En Progreso',
      2 => 'Finalizada'
    ];

    return $estados[$this->status] ?? 'Desconocido';
  }

  public function getMaxScoreCompetenceAttribute()
  {
    return $this->competenceParameter?->details()->max('to');
  }
}
