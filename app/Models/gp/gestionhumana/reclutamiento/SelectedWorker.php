<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Seleccionado / trabajador — proyeccion de `rrhh_persona` para el flujo de
 * Reclutamiento F2 (idVista 71, `SeleccionadoController` del legacy).
 * Cubre tipo_trabajador_id 6 (SELECCIONADO, en proceso de contratacion) y
 * 2 (CONTRATADO, ya con alta).
 */
class SelectedWorker extends BaseModel
{
  use Reportable;

  protected $table = 'rrhh_persona';

  const STATUS_CARTA_OFERTA_PENDIENTE  = 20;
  const STATUS_CARTA_OFERTA_COMPLETADO = 21;

  const STATUS_ALTA = 22;
  const STATUS_BAJA = 23;

  protected $fillable = [
    'nombre_completo',
    'vat',
    'sede_id',
    'area_id',
    'cargo_id',
    'centro_costo_id',
    'proceso_postulacion_id',
    'tipo_trabajador_id',
    'jefe_id',
    'supervisor_id',
    'motivo_status',
    'fecha_inicio',
    'presupuesto',
    'sueldo',
    'carta_oferta',
    'status_carta_oferta_id',
    'status_envio_mail_carta_oferta',
    'fecha_envio_mail_carta_oferta',
    'cuenta_interbancaria_cts',
    'cuenta_interbancaria_haberes',
    'cta_haberes',
    'entidad_haberes',
    'cta_cts',
    'entidad_cts',
    'vidaley',
    'estado_sctr',
    'entidad_sctr',
    'essaludvida',
    'escolaridad',
    'monto_escolaridad',
    'asignacion',
    'sis_pensiones_id',
    'cuspp',
    'fecha_ingreso_afp_snp',
    'status_id',
    'status_deleted',
    'b_empleado',
  ];

  protected $casts = [
    'fecha_inicio'                   => 'date:Y-m-d',
    'fecha_envio_mail_carta_oferta'  => 'datetime',
    'fecha_ingreso_afp_snp'          => 'date:Y-m-d',
  ];

  const filters = [
    'id'                 => '=',
    'search'             => ['nombre_completo', 'vat', 'email'],
    'nombre_completo'    => 'like',
    'vat'                => 'like',
    'sede_id'            => '=',
    'tipo_trabajador_id' => 'in_or_equal',
  ];

  const sorts = ['id', 'nombre_completo', 'created_at'];

  protected $reportColumns = [
    'id'                 => ['label' => 'ID', 'width' => 8],
    'nombre_completo'    => ['label' => 'NOMBRE COMPLETO', 'width' => 30],
    'vat'                => ['label' => 'DOCUMENTO', 'width' => 15],
    'email'              => ['label' => 'EMAIL', 'width' => 25],
    'sede.abreviatura'   => ['label' => 'SEDE', 'width' => 15],
    'area.name'          => ['label' => 'ÁREA', 'width' => 20],
    'position.name'      => ['label' => 'CARGO', 'width' => 20],
    'sueldo'             => ['label' => 'SUELDO', 'width' => 12, 'formatter' => 'currency'],
    'fecha_inicio'       => ['label' => 'FECHA INICIO', 'width' => 14, 'formatter' => 'date'],
    'cartaOfertaLabel'   => ['label' => 'CARTA OFERTA', 'width' => 16, 'accessor' => 'cartaOfertaLabel'],
    'lifeStatusLabel'    => ['label' => 'ESTADO', 'width' => 14, 'accessor' => 'lifeStatusLabel'],
  ];

  protected $reportRelations = ['sede', 'area', 'position'];

  public function cartaOfertaLabel(): string
  {
    return match ((int) $this->status_carta_oferta_id) {
      self::STATUS_CARTA_OFERTA_COMPLETADO => 'Firmada',
      self::STATUS_CARTA_OFERTA_PENDIENTE  => 'Pendiente',
      default                              => 'Sin enviar',
    };
  }

  public function lifeStatusLabel(): string
  {
    return match ((int) $this->status_id) {
      self::STATUS_ALTA => 'Activo',
      self::STATUS_BAJA => 'Cesado',
      default           => 'En proceso',
    };
  }

  protected static function booted(): void
  {
    static::addGlobalScope('selectedWorker', fn(Builder $b) => $b
      ->where('rrhh_persona.status_deleted', 1)
      ->where('rrhh_persona.b_empleado', 1)
      ->whereIn('rrhh_persona.tipo_trabajador_id', [
        Applicant::TIPO_SELECCIONADO,
        Applicant::TIPO_CONTRATADO,
      ]));
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

  public function process()
  {
    return $this->belongsTo(RecruitmentProcess::class, 'proceso_postulacion_id');
  }

  public function user()
  {
    return $this->hasOne(User::class, 'partner_id', 'id');
  }

  public function boss()
  {
    return $this->belongsTo(self::class, 'jefe_id')->withoutGlobalScopes();
  }

  public function supervisor()
  {
    return $this->belongsTo(self::class, 'supervisor_id')->withoutGlobalScopes();
  }

  public function relatives()
  {
    return $this->hasMany(Relative::class, 'persona_id');
  }

  public function workExperiences()
  {
    return $this->hasMany(WorkExperience::class, 'persona_id');
  }
}
