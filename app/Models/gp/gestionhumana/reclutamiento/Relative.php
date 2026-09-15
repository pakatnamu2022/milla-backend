<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Familiar / carga familiar del trabajador (`rrhh_parientes`).
 */
class Relative extends BaseModel
{
  protected $table = 'rrhh_parientes';

  protected $fillable = [
    'persona_id',
    'nombre_completo',
    'dni',
    'fecha_nacimiento',
    'sexo',
    'parentesco',
    'dni_adjunto',
    'status_id',
    'status_deleted',
    'write_id',
  ];

  const filters = [
    'id'         => '=',
    'persona_id' => '=',
  ];

  const sorts = ['id', 'created_at'];

  protected static function booted(): void
  {
    static::addGlobalScope('relative', fn(Builder $b) => $b->where('rrhh_parientes.status_deleted', 1));
  }
}
