<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person_detail';

  protected $fillable = [
    'person_id'
  ];

  public function person()
  {
    return $this->hasOne(Person::class, 'person_id');
  }
}
