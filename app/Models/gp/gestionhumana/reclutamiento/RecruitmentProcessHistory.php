<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trazabilidad del proceso de reclutamiento (`rrhh_proceso_historial`):
 * pausas, reanudaciones y ampliaciones de plazo, con quién y cuándo.
 */
class RecruitmentProcessHistory extends BaseModel
{
  protected $table = 'rrhh_proceso_historial';

  const ACCION_CREADO = 'creado';
  const ACCION_PAUSADO = 'pausado';
  const ACCION_REANUDADO = 'reanudado';
  const ACCION_DIAS_ADICIONALES = 'dias_adicionales';
  const ACCION_CERRADO = 'cerrado';
  const ACCION_REABIERTO = 'reabierto';

  protected $fillable = [
    'proceso_postulacion_id',
    'accion',
    'detalle',
    'dias_agregados',
    'usuario_id',
  ];

  public $timestamps = true;

  const sorts = ['id', 'created_at'];

  public function process(): BelongsTo
  {
    return $this->belongsTo(RecruitmentProcess::class, 'proceso_postulacion_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'usuario_id');
  }
}
