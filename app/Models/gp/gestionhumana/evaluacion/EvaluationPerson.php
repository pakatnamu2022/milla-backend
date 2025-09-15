<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPerson extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person';

  protected $fillable = [
    'person_id',
    'chief_id',
    'chief',
    'person_cycle_detail_id',
    'evaluation_id',
    'result',
    'compliance',
    'qualification',
    'comment',
    'wasEvaluated',
  ];

  const filters = [
    'search' => '[person.nombre_completo]',
    'person_id' => '=',
    'person_cycle_detail_id' => '=',
    'evaluation_id' => '=',
    'result' => '=',
    'compliance' => '=',
    'qualification' => '=',
    'chief_id' => '=',
    'chief' => '=',
    'wasEvaluated' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'person_id',
    'person_cycle_detail_id',
    'evaluation_id',
    'result',
    'compliance',
    'qualification',
  ];

  public function person()
  {
    return $this->belongsTo(Person::class, 'person_id');
  }

  public function personCycleDetail()
  {
    return $this->belongsTo(EvaluationPersonCycleDetail::class, 'person_cycle_detail_id');
  }

  public function evaluation()
  {
    return $this->belongsTo(Evaluation::class, 'evaluation_id');
  }
}
