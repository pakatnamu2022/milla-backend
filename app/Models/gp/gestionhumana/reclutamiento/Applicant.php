<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Postulante — proyeccion de `rrhh_persona` para el flujo de Reclutamiento F1.
 * Equivale al idVista 52 de web_millagp_2 (AdministracionPostulanteController).
 *
 * tipo_trabajador_id: 1 = POSTULANTE, 2 = CONTRATADO, 3 = RECHAZADO,
 * 4 = FUERA DE CUPO, 5 = LISTA NEGRA, 6 = SELECCIONADO.
 */
class Applicant extends BaseModel
{
  protected $table = 'rrhh_persona';

  const TIPO_POSTULANTE   = 1;
  const TIPO_CONTRATADO   = 2;
  const TIPO_RECHAZADO    = 3;
  const TIPO_FUERA_CUPO   = 4;
  const TIPO_LISTA_NEGRA  = 5;
  const TIPO_SELECCIONADO = 6;

  const STATUS_TYPES = [
    self::TIPO_SELECCIONADO,
    self::TIPO_RECHAZADO,
    self::TIPO_FUERA_CUPO,
    self::TIPO_LISTA_NEGRA,
  ];

  protected $fillable = [
    'nombre_completo',
    'vat',
    'vat2',
    'vat3',
    'tipo_doc',
    'sexo',
    'fecha_nacimiento',
    'estado_civil',
    'fecha_estado_civil',
    'nacionalidad',
    'lugar_nacimiento',
    'ubigeo',
    'email',
    'cel_personal',
    'cel_refencia',
    'tel_referencia_2',
    'direccion_principal',
    'direccion_ref',
    'distrito',
    'provincia',
    'departamento',
    'brevete_matpel',
    'clase_brev',
    'categoria_brev',
    'estudios_id',
    'escolaridad',
    'estado_estudios_prim',
    'centro_estudios_prim',
    'estado_estudios_sec',
    'centro_estudios_sec',
    'institucion_tec_univ',
    'carrera_tec_univ',
    'ciudad_dep_est_tec_univ',
    'nivel_alcanzado',
    'ciclo_estudios',
    'anos_curso',
    'grado_obtenido',
    'cv_actualizado',
    'foto_adjunto',
    'sede_id',
    'area_id',
    'cargo_id',
    'centro_costo_id',
    'proceso_postulacion_id',
    'tipo_trabajador_id',
    'jefe_id',
    'motivo_status',
    'b_empleado',
    'status_id',
    'status_deleted',
  ];

  protected $casts = [
    'fecha_nacimiento'   => 'date:Y-m-d',
    'fecha_estado_civil' => 'date:Y-m-d',
  ];

  const filters = [
    'id'                     => '=',
    'search'                 => ['nombre_completo', 'vat', 'email'],
    'nombre_completo'        => 'like',
    'vat'                    => 'like',
    'proceso_postulacion_id' => '=',
    'sede_id'                => '=',
    'area_id'                => '=',
    'cargo_id'               => '=',
    'tipo_trabajador_id'     => 'in_or_equal',
  ];

  const sorts = ['id', 'nombre_completo', 'created_at'];

  protected static function booted(): void
  {
    static::addGlobalScope('applicant', fn(Builder $b) => $b
      ->where('rrhh_persona.status_deleted', 1)
      ->where('rrhh_persona.b_empleado', 1)
      ->whereNotNull('rrhh_persona.proceso_postulacion_id')
      ->whereIn('rrhh_persona.tipo_trabajador_id', [
        self::TIPO_POSTULANTE,
        self::TIPO_RECHAZADO,
        self::TIPO_FUERA_CUPO,
        self::TIPO_LISTA_NEGRA,
        self::TIPO_SELECCIONADO,
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
}
