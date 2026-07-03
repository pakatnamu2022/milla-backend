<?php

namespace App\Models\gp\gestionhumana\permiso;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrabajadorPermiso extends Model
{
  protected $table = 'rrhh_trabajador_permiso';

  protected $fillable = [
    'partner_id',
    'fecha_inicio',
    'fecha_fin',
    'c_motivo',
    'sin_goce',
    'write_id',
    'sucursal_id',
    'status_deleted',
  ];

  const filters = [
    'search'     => ['empleado.nombre_completo'],
    'partner_id' => '=',
    'sin_goce'   => '=',
    'fecha_from' => 'scope',
    'fecha_to'   => 'scope',
  ];

  const sorts = ['id', 'fecha_inicio', 'fecha_fin', 'partner_id'];

  protected $casts = [
    'fecha_inicio' => 'date',
    'fecha_fin'    => 'date',
  ];

  public function scopeFechaFrom(Builder $query, string $value): Builder
  {
    return $query->whereDate('fecha_inicio', '>=', $value);
  }

  public function scopeFechaTo(Builder $query, string $value): Builder
  {
    return $query->whereDate('fecha_fin', '<=', $value);
  }

  public function empleado(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'partner_id');
  }
}