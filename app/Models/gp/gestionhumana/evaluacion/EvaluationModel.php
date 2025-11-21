<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;

class EvaluationModel extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_model';

  protected $fillable = [
    'categories',
    'leadership_weight',
    'self_weight',
    'par_weight',
    'report_weight',
  ];

  const filters = [
    'leadership_weight' => '=',
    'self_weight' => '=',
    'par_weight' => '=',
    'report_weight' => '=',
  ];

  const sorts = [
    'leadership_weight',
    'self_weight',
    'par_weight',
    'report_weight',
  ];

  /**
   * Relations
   */
  public function categories()
  {
    $categoryIds = explode(',', $this->categories);
    return HierarchicalCategory::whereIn('id', $categoryIds)->get();
  }

  public function setCategoriesAttribute($value)
  {
    if (is_array($value)) {
      $this->attributes['categories'] = implode(',', $value);
    } else {
      $this->attributes['categories'] = $value;
    }
  }

}
