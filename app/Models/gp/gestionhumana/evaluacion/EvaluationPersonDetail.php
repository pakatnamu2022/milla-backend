<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person_detail';

  protected $fillable = [
    'person_id'
  ];

  const filters = [
    'search' => ['person.nombre_completo'],
  ];

  public function person()
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
