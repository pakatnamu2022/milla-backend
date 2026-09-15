<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Experiencia laboral declarada por el trabajador (`rrhh_experiencia_laboral`).
 */
class WorkExperience extends BaseModel
{
  protected $table = 'rrhh_experiencia_laboral';

  protected $fillable = [
    'persona_id',
    'institucion',
    'cargo_ocupado',
    'motivo_cese',
    'telefono',
    'periodo',
    'jefe_directo',
    'cargo_jefe',
    'funciones',
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
    static::addGlobalScope('workExperience', fn(Builder $b) => $b->where('rrhh_experiencia_laboral.status_deleted', 1));
  }
}
