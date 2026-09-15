<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionsistema\Status;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Proceso de postulacion / vacante, tabla legacy `rrhh_proceso_postulacion`.
 * Equivale al idVista 50 de web_millagp_2 (ProcesoPostulacionController).
 *
 * status_id: 9 = ABIERTO, 10 = EN PROCESO, 11 = CERRADO (config_status tipo_4).
 */
class RecruitmentProcess extends BaseModel
{
  use Reportable;

  protected $table = 'rrhh_proceso_postulacion';

  const STATUS_OPEN = 9;
  const STATUS_IN_PROCESS = 10;
  const STATUS_CLOSED = 11;

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

  protected $reportColumns = [
    'id'                 => ['label' => 'ID', 'width' => 8],
    'nombre_postulacion' => ['label' => 'PROCESO', 'width' => 30],
    'sede.abreviatura'   => ['label' => 'SEDE', 'width' => 15],
    'area.name'          => ['label' => 'ÁREA', 'width' => 20],
    'position.name'      => ['label' => 'CARGO', 'width' => 20],
    'cant_trab_solicita' => ['label' => 'VACANTES', 'width' => 10, 'formatter' => 'number'],
    'status.estado'      => ['label' => 'ESTADO', 'width' => 15],
    'fecha_inicio'       => ['label' => 'FECHA INICIO', 'width' => 14, 'formatter' => 'date'],
    'fecha_fin_plazo'    => ['label' => 'FECHA FIN PLAZO', 'width' => 14, 'formatter' => 'date'],
    'fecha_fin_cierre'   => ['label' => 'FECHA CIERRE', 'width' => 14, 'formatter' => 'date'],
    'dias_plazo'         => ['label' => 'DÍAS PLAZO', 'width' => 10, 'formatter' => 'number'],
  ];

  protected $reportRelations = ['sede', 'area', 'position', 'status'];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }

  public function setNombrePostulacionAttribute($value): void
  {
    $this->attributes['nombre_postulacion'] = Str::upper(Str::ascii($value));
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
