<?php

namespace App\Models\gp\gestionhumana\ausentismo;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AusentismoLaboral extends Model
{
  protected $table = 'rrhh_ausentismo_laboral';

  protected $fillable = [
    'empleado_id',
    'fecha_inicial',
    'fecha_fin',
    'id_tipo_descanso',
    'mes',
    'anio',
    'motivo',
    'tipo_contingencia',
    'fecha_contingencia',
    'atencion',
    'diagnostico',
    'citt',
    'centro_atencion',
    'sede_id',
    'area_id',
    'estado',
    'estado_aprobacion',
    'fecha_aprobacion',
    'status_deleted',
    'creator_user',
    'updater_user',
  ];

  const filters = [
    'search'          => ['empleado.nombre_completo'],
    'empleado_id'     => '=',
    'id_tipo_descanso'=> '=',
    'estado'          => '=',
    'sede_id'         => '=',
    'area_id'         => '=',
    'fecha_from'      => 'scope',
    'fecha_to'        => 'scope',
  ];

  const sorts = [
    'id',
    'fecha_inicial',
    'fecha_fin',
    'empleado_id',
    'id_tipo_descanso',
    'estado',
  ];

  protected $casts = [
    'fecha_inicial'    => 'date',
    'fecha_fin'        => 'date',
    'fecha_contingencia' => 'date',
    'fecha_aprobacion' => 'datetime',
  ];

  public function scopeFechaFrom(Builder $query, string $value): Builder
  {
    return $query->whereDate('fecha_inicial', '>=', $value);
  }

  public function scopeFechaTo(Builder $query, string $value): Builder
  {
    return $query->whereDate('fecha_fin', '<=', $value);
  }

  public function empleado(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'empleado_id');
  }

  public function tipoDescanso(): BelongsTo
  {
    return $this->belongsTo(TipoDescanso::class, 'id_tipo_descanso');
  }
}
