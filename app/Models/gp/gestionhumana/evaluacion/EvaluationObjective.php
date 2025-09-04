<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationObjective extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_objective';

  protected $fillable = [
    'name',
    'description',
    'goalReference',
    'fixedWeight',
    'isAscending', //bool: true si a mayor es mejor, false si a menor es mejor
    'metric_id'
  ];

  const filters = [
    'id' => '=',
    'search' => ['name', 'description'],
    'metric_id' => '=',
  ];

  const sorts = [
    'id',
    'name',
    'description',
    'metric_id',
  ];


  public function metric()
  {
    return $this->belongsTo(EvaluationMetric::class, 'metric_id');
  }

}
