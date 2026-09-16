<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entrevista de un postulante dentro de un proceso de reclutamiento
 * (`rrhh_entrevista`). No todo postulante llega a esta etapa — se crea solo
 * para los que avanzan de "postulante" a "entrevista". `resultado_promedio`
 * es el promedio de las calificaciones por subcompetencia (`scores`),
 * recalculado con `recalculateResult()`.
 */
class Interview extends BaseModel
{
  use Reportable, SoftDeletes;

  protected $table = 'rrhh_entrevista';

  const FASE_RRHH = 1;
  const FASE_JEFE = 2;

  const FASE_LABELS = [
    self::FASE_RRHH => 'Entrevista RRHH',
    self::FASE_JEFE => 'Entrevista con jefe',
  ];

  protected $fillable = [
    'proceso_postulacion_id',
    'persona_id',
    'fase',
    'entrevistador_id',
    'fecha_entrevista',
    'resultado_promedio',
    'observaciones',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'fecha_entrevista'    => 'datetime',
    'resultado_promedio'  => 'decimal:2',
  ];

  const filters = [
    'proceso_postulacion_id' => '=',
    'persona_id'              => '=',
    'entrevistador_id'        => '=',
    'fase'                    => '=',
  ];

  const sorts = ['id', 'fecha_entrevista', 'resultado_promedio'];

  protected $reportColumns = [
    'id'                         => ['label' => 'ID', 'width' => 8],
    'process.nombre_postulacion' => ['label' => 'PROCESO', 'width' => 30],
    'applicant.nombre_completo'  => ['label' => 'POSTULANTE', 'width' => 30],
    'interviewer.name'           => ['label' => 'ENTREVISTADOR', 'width' => 25],
    'fecha_entrevista'           => ['label' => 'FECHA', 'width' => 16, 'formatter' => 'datetime'],
    'resultado_promedio'         => ['label' => 'RESULTADO', 'width' => 12, 'formatter' => 'number'],
  ];

  protected $reportRelations = ['process', 'applicant', 'interviewer'];

  public function process(): BelongsTo
  {
    return $this->belongsTo(RecruitmentProcess::class, 'proceso_postulacion_id');
  }

  public function applicant(): BelongsTo
  {
    return $this->belongsTo(Applicant::class, 'persona_id');
  }

  public function interviewer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'entrevistador_id');
  }

  public function scores(): HasMany
  {
    return $this->hasMany(InterviewScore::class, 'entrevista_id');
  }

  /**
   * Recalcula resultado_promedio como el promedio de las calificaciones
   * registradas y lo persiste. Se llama después de guardar/actualizar una
   * InterviewScore.
   */
  public function recalculateResult(): void
  {
    $average = $this->scores()->whereNotNull('puntaje')->avg('puntaje');

    $this->update(['resultado_promedio' => $average]);
  }
}
