<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCategoryCompetenceDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_category_competence';

  protected $fillable = [
    'competence_id',
    'category_id',
    'person_id',
    'active'
  ];

  const filters = [
    'id' => '=',
    'competence_id' => '=',
    'category_id' => '=',
    'person_id' => '=',
    'active' => '=',
    'search' => ['competence_id', 'category_id', 'person_id'],
  ];

  const sorts = [
    'id',
    'competence_id',
    'category_id',
    'person_id',
    'active',
    'created_at',
    'updated_at'
  ];

  public function competence()
  {
    return $this->belongsTo(EvaluationCompetence::class, 'competence_id');
  }

  public function category()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'category_id');
  }

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
