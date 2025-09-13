<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCycleCategoryDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_cycle_category_detail';

  protected $fillable = [
    'cycle_id',
    'hierarchical_category_id',
    'deleted_at'
  ];

  const filters = [
    'search' => ['']
  ];

  public function cycle()
  {
    return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
  }

  public function hierarchicalCategory()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'hierarchical_category_id');
  }
}
