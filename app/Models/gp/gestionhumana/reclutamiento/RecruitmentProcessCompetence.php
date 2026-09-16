<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationSubCompetence;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subcompetencias configuradas para la entrevista de un proceso de
 * reclutamiento (`rrhh_proceso_competencia`). El usuario elige, por proceso,
 * qué subcompetencias de `gh_config_subcompetencias` se calificarán en la
 * entrevista de cada postulante.
 */
class RecruitmentProcessCompetence extends BaseModel
{
  protected $table = 'rrhh_proceso_competencia';

  protected $fillable = [
    'proceso_postulacion_id',
    'sub_competencia_id',
    'orden',
  ];

  const filters = [
    'proceso_postulacion_id' => '=',
  ];

  const sorts = ['id', 'orden'];

  public function process(): BelongsTo
  {
    return $this->belongsTo(RecruitmentProcess::class, 'proceso_postulacion_id');
  }

  public function subCompetence(): BelongsTo
  {
    return $this->belongsTo(EvaluationSubCompetence::class, 'sub_competencia_id');
  }
}
