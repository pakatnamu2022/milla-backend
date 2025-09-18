<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonResult extends BaseModel
{
  use SoftDeletes, Reportable;

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

  // ← AGREGAR ESTA CONFIGURACIÓN
  protected $reportColumns = [
    'id' => [
      'label' => 'ID',
      'formatter' => 'number',
      'width' => 8
    ],
    'person.nombre_completo' => [
      'label' => 'Empleado',
      'formatter' => null,
      'width' => 25
    ],
    'evaluation.name' => [
      'label' => 'Evaluación',
      'formatter' => null,
      'width' => 25
    ],
    'competencesPercentage' => [
      'label' => '% Competencias',
      'formatter' => 'percentage',
      'width' => 12
    ],
    'objectivesPercentage' => [
      'label' => '% Objetivos',
      'formatter' => 'percentage',
      'width' => 12
    ],
    'competencesResult' => [
      'label' => 'Resultado Competencias',
      'formatter' => 'decimal',
      'width' => 15
    ],
    'objectivesResult' => [
      'label' => 'Resultado Objetivos',
      'formatter' => 'decimal',
      'width' => 15
    ],
    'result' => [
      'label' => 'Resultado Final',
      'formatter' => 'decimal',
      'width' => 12
    ]
  ];

  protected $reportRelations = ['person', 'evaluation'];


}
