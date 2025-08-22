<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;

class EvaluationCategoryObjectiveDetail extends Model
{
  protected $table = 'gh_evaluation_category_objective_detail';

  protected $fillable = [
    'objective_id',
    'category_id',
  ];

  const filters = [
    'id' => '=',
    'objective_id' => '=',
    'category_id' => '=',
  ];

  const sorts = [
    'id',
    'objective_id',
    'category_id',
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
