<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationSubCompetence;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Calificación de una subcompetencia dentro de una entrevista
 * (`rrhh_entrevista_calificacion`). Una fila por subcompetencia configurada
 * en `RecruitmentProcessCompetence` para el proceso de esa entrevista.
 */
class InterviewScore extends BaseModel
{
  protected $table = 'rrhh_entrevista_calificacion';

  protected $fillable = [
    'entrevista_id',
    'sub_competencia_id',
    'puntaje',
  ];

  protected $casts = [
    'puntaje' => 'decimal:2',
  ];

  const filters = [
    'entrevista_id' => '=',
  ];

  protected static function booted(): void
  {
    static::saved(fn(self $score) => $score->interview?->recalculateResult());
    static::deleted(fn(self $score) => $score->interview?->recalculateResult());
  }

  public function interview(): BelongsTo
  {
    return $this->belongsTo(Interview::class, 'entrevista_id');
  }

  public function subCompetence(): BelongsTo
  {
    return $this->belongsTo(EvaluationSubCompetence::class, 'sub_competencia_id');
  }
}
