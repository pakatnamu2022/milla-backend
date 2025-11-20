<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationParEvaluator extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_par_evaluator';

  protected $fillable = [
    'worker_id',
    'mate_id',
  ];

  const filters = [
    'worker_id' => '=',
    'start_date' => '=',
    'end_date' => '=',
    'active' => '=',
  ];

  const sorts = [
    'name',
    'start_date',
    'end_date',
    'active',
  ];

  /**
   * Relations
   */

  public function worker()
  {
    return $this->belongsTo(EvaluationPerson::class, 'worker_id');
  }

  public function mate()
  {
    return $this->belongsTo(EvaluationPerson::class, 'mate_id');
  }
}
