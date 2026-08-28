<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Historial de estado del trabajador (activo/cese), tabla legacy `rrhh_estado_trabajador`.
 * Hoy coexiste con el sistema legacy que sigue escribiendo aqui directamente;
 * el objetivo a futuro es centralizar en milla-backend toda la administracion del cese
 * (LBS automatica, entrega de equipos, etc. - ver Fase 4 del plan de implementacion).
 */
class WorkerStatusHistory extends BaseModel
{
  protected $table = 'rrhh_estado_trabajador';

  protected $fillable = [
    'empleado_id',
    'fecha',
    'estado',
    'motivo',
    'sucursal_id',
    'write_id',
    'status_deleted',
  ];

  const int STATUS_ACTIVE = 22;
  const int STATUS_TERMINATED = 23;

  const filters = [
    'empleado_id' => '=',
    'estado'      => '=',
    'sucursal_id' => '=',
    'fecha'       => '>=',
  ];

  const sorts = ['fecha', 'id'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }

  public function employee()
  {
    return $this->belongsTo(Worker::class, 'empleado_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sucursal_id');
  }

  public function writeUser()
  {
    return $this->belongsTo(User::class, 'write_id');
  }
}
