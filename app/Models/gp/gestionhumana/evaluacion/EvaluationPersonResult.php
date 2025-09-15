<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonResult extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person_result';

  protected $fillable = [
    'person_id',
    'evaluation_id',
    'competencesPercentage',
    'objectivesPercentage',
    'objectivesResult',
    'competencesResult',
    'result',
  ];

  const filters = [
    'search' => ['person.nombre_completo'],
    'person_id' => '=',
    'person.cargo_id' => '=',
    'evaluation_id' => '=',
    'competencesPercentage' => '=',
    'objectivesPercentage' => '=',
    'objectivesResult' => '=',
    'competencesResult' => '=',
    'result' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'person_id',
    'evaluation_id',
    'competencesPercentage',
    'objectivesPercentage',
    'objectivesResult',
    'competencesResult',
    'result',
  ];

  public function person()
  {
    return $this->belongsTo(Person::class, 'person_id');
  }

  public function evaluation()
  {
    return $this->belongsTo(Evaluation::class, 'evaluation_id');
  }

  public function details()
  {
    return $this->hasMany(EvaluationPerson::class, 'evaluation_id', 'evaluation_id')
      ->where('person_id', $this->person_id);
  }

  public function competenceDetails()
  {
    return $this->hasMany(EvaluationPersonCompetenceDetail::class, 'evaluation_id', 'evaluation_id')
      ->where('person_id', $this->person_id);
  }


}
