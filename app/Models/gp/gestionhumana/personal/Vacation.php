<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Vacation extends BaseModel
{
  use Reportable;

  protected $table = 'rrhh_vacaciones';

  protected $fillable = [
    'empleado_id',
    'fecha_inicio',
    'fecha_fin',
    'tipo',
    'periodo_inicio',
    'periodo_fin',
    'observacion',
    'status_id',
    'aprobacion_jefatura',
    'fecha_aprobacion_jefatura',
    'user_jefatura_id',
    'aprobacion_rrhh',
    'fecha_aprobacion_rrhh',
    'user_id_rrhh',
    'sede_id',
    'write_id',
    'status_deleted',
  ];

  const filters = [
    'id'                 => '=',
    'empleado_id'        => '=',
    'sede_id'            => '=',
    'status_id'          => '=',
    'tipo'               => '=',
    'aprobacion_jefatura' => '=',
    'aprobacion_rrhh'    => '=',
    'fecha_inicio'       => '>=',
    'fecha_fin'          => '<=',
  ];

  const sorts = ['fecha_inicio', 'fecha_fin', 'id'];

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
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function status()
  {
    return $this->belongsTo(Status::class, 'status_id');
  }

  public function writeUser()
  {
    return $this->belongsTo(User::class, 'write_id');
  }

  public function jefaturaUser()
  {
    return $this->belongsTo(User::class, 'user_jefatura_id');
  }

  public function rrhhUser()
  {
    return $this->belongsTo(User::class, 'user_id_rrhh');
  }
}
