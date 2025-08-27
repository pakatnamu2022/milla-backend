<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCategoryObjectiveDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_category_objective';

  protected $fillable = [
    'objective_id',
    'category_id',
    'goal',
    'weight',
    'fixedWeight',
  ];

  const filters = [
    'id' => '=',
    'objective_id' => '=',
    'category_id' => '=',
    'goal' => '=',
    'weight' => '=',
  ];

  const sorts = [
    'id',
    'objective_id',
    'category_id',
    'goal',
    'weight',
  ];

  protected $casts = [
    'fixedWeight' => 'boolean',
  ];

  public function objective()
  {
    return $this->belongsTo(EvaluationObjective::class, 'objective_id');
  }

  public function category()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'category_id');
  }
}
