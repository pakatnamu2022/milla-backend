<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCycle extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_cycle';

  protected $fillable = [
    'name',
    'status', // 'pendiente', 'en proceso', 'cerrado'
    'typeEvaluation', // 0: Objetivos, 1: 180 o 360
    'start_date',
    'end_date',
    'start_date_objectives',
    'end_date_objectives',
    'period_id',
    'parameter_id'
  ];

  const filters = [
    'search' => [
      'name',
    ],
    'name' => 'like',
    'start_date' => 'date',
    'end_date' => 'date',
    'start_date_objectives' => 'date',
    'end_date_objectives' => 'date',
    'period_id' => 'equals',
    'parameter_id' => 'equals'
  ];

  const sorts = [
    'search',
    'name',
    'start_date',
    'end_date',
    'start_date_objectives',
    'end_date_objectives',
    'period_id',
    'parameter_id'
  ];

  const statusList = [
    'pendiente' => 'Pendiente',
    'en proceso' => 'En Proceso',
    'cerrado' => 'Cerrado',
  ];

  public function period()
  {
    return $this->belongsTo(EvaluationPeriod::class, 'period_id');
  }

  public function parameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'parameter_id');
  }

  public function categories()
  {
    return $this->hasMany(EvaluationCycleCategoryDetail::class, 'cycle_id');
  }

}
