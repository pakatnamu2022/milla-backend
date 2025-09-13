<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonCompetenceDetail extends Model
{
  use SoftDeletes;

  protected $table = 'gh_evaluation_person_competence_detail';

  protected $fillable = [
    'evaluation_id',
    'person_id',
    'competence_id',
    'sub_competence_id',
    'person',
    'competence',
    'sub_competence',
    'evaluatorType',
    'result'
  ];

  protected $casts = [
    'result' => 'decimal:2'
  ];

  // Filtros para BaseService si los necesitas
  const filters = [
    'evaluation_id' => '=',
    'person_id' => '=',
    'competence_id' => '=',
    'evaluatorType' => '=',
    'person' => 'like',
    'competence' => 'like',
    'search' => ['person', 'competence', 'sub_competence']
  ];

  const sorts = [
    'id',
    'person',
    'competence',
    'sub_competence',
    'evaluatorType',
    'result',
    'created_at'
  ];

  public function evaluation()
  {
    return $this->belongsTo(Evaluation::class, 'evaluation_id');
  }

  public function person()
  {
    return $this->belongsTo(Person::class, 'person_id');
  }

  public function competence()
  {
    // Ajusta la ruta del modelo según tu estructura
    return $this->belongsTo(EvaluationCompetence::class, 'competence_id');
  }

  public function subCompetence()
  {
    // Ajusta la ruta del modelo según tu estructura
    return $this->belongsTo(EvaluationSubCompetence::class, 'sub_competence_id');
  }

  public function getTipoEvaluadorTextoAttribute()
  {
    $tipos = [
      0 => 'Líder Directo',
      1 => 'Autoevaluación',
      2 => 'Compañeros',
      3 => 'Reportes'
    ];

    return $tipos[$this->evaluatorType] ?? 'Desconocido';
  }
}
