<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Builder;

/**
 * Proceso de postulacion / vacante, tabla legacy `rrhh_proceso_postulacion`.
 * Equivale al idVista 50 de web_millagp_2 (ProcesoPostulacionController).
 *
 * status_id: 9 = ABIERTO, 10 = EN PROCESO, 11 = CERRADO (config_status tipo_4).
 */
class RecruitmentProcess extends BaseModel
{
  protected $table = 'rrhh_proceso_postulacion';

  const STATUS_OPEN       = 9;
  const STATUS_IN_PROCESS = 10;
  const STATUS_CLOSED     = 11;

  protected $fillable = [
    'nombre_postulacion',
    'status_id',
    'cant_trab_solicita',
    'sede_id',
    'area_id',
    'cargo_id',
    'centro_costo_id',
    'fecha_inicio',
    'fecha_fin_plazo',
    'fecha_fin_cierre',
    'dias_plazo',
    'status_deleted',
  ];

  protected $casts = [
    'fecha_inicio'     => 'date:Y-m-d',
    'fecha_fin_plazo'  => 'date:Y-m-d',
    'fecha_fin_cierre' => 'date:Y-m-d',
  ];

  const filters = [
    'id'                 => '=',
    'search'             => ['nombre_postulacion'],
    'nombre_postulacion' => 'like',
    'status_id'          => '=',
    'sede_id'            => '=',
    'area_id'            => '=',
    'cargo_id'           => '=',
    'fecha_inicio'       => 'date_between',
  ];

  const sorts = ['id', 'nombre_postulacion', 'fecha_inicio', 'fecha_fin_plazo'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area()
  {
    return $this->belongsTo(Area::class, 'area_id');
  }

  public function position()
  {
    return $this->belongsTo(Position::class, 'cargo_id');
  }

  public function status()
  {
    return $this->belongsTo(Status::class, 'status_id');
  }

  /**
   * Postulantes registrados contra este proceso (rrhh_persona.proceso_postulacion_id).
   */
  public function applicants()
  {
    return $this->hasMany(Worker::class, 'proceso_postulacion_id')->withoutGlobalScopes();
  }
}
