<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCycle extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'gh_evaluation_cycle';

  protected $fillable = [
    'name',
    'status', // 'pendiente', 'en proceso', 'cerrado'
    'typeEvaluation', // 0: Objetivos, 1: 180 o 360
    'start_date',
    'end_date',
    'cut_off_date',
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

  // En el modelo EvaluationCycle
  public function workers()
  {
    return $this->hasManyThrough(
      Worker::class,
      EvaluationCycleCategoryDetail::class,
      'cycle_id',                    // Foreign key en EvaluationCycleCategoryDetail
      'cargo_id',                    // Foreign key en Person que apunta a Position
      'id',                         // Local key en EvaluationCycle
      'hierarchical_category_id'     // Local key en EvaluationCycleCategoryDetail que conecta con HierarchicalCategory
    )
      ->distinct();
  }

  protected $reportColumns = [
    'name' => [
      'label' => 'Nombre',
      'formatter' => null,
      'width' => 8
    ],
    'status' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 25
    ],
    'typeEvaluation' => [
      'label' => 'Tipo de Evaluación',
      'formatter' => null,
      'width' => 25
    ],
    'start_date' => [
      'label' => 'Fecha de Inicio',
      'formatter' => 'date',
      'width' => 15
    ],
    'end_date' => [
      'label' => 'Fecha de Fin',
      'formatter' => 'date',
      'width' => 15
    ],
    'cut_off_date' => [
      'label' => 'Fecha de Corte',
      'formatter' => 'date',
      'width' => 15
    ],
    'start_date_objectives' => [
      'label' => 'Inicio Objetivos',
      'formatter' => 'date',
      'width' => 15
    ],
    'end_date_objectives' => [
      'label' => 'Fin Objetivos',
      'formatter' => 'date',
      'width' => 15
    ]
  ];


}
