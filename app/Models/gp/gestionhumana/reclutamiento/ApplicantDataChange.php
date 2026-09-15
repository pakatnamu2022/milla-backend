<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;

/**
 * Cambio de ficha propuesto por el postulante desde su portal, tabla legacy
 * `rrhh_temp_data_persona`. Cola de aprobacion del idVista 52
 * (AdministracionPostulanteController::aprobar / rechazar).
 *
 * status_id: 17 = PENDIENTE, 18 = RECHAZADO, 19 = APROBADO (config_status tipo_7).
 */
class ApplicantDataChange extends BaseModel
{
  protected $table = 'rrhh_temp_data_persona';

  const STATUS_PENDING  = 17;
  const STATUS_REJECTED = 18;
  const STATUS_APPROVED = 19;

  protected $fillable = [
    'empleado_id',
    'nombre_completo',
    'vat',
    'vat2',
    'vat3',
    'brevete_matpel',
    'clase_brev',
    'categoria_brev',
    'fecha_nacimiento',
    'nacionalidad',
    'estudios_id',
    'ubigeo',
    'email',
    'cel_personal',
    'cel_referencia',
    'tel_referencia_2',
    'direccion_principal',
    'direccion_ref',
    'distrito',
    'provincia',
    'departamento',
    'escolaridad',
    'lugar_nacimiento',
    'estado_civil',
    'fecha_estado_civil',
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
    'proceso_postulacion_id',
    'sexo',
    'cv_actualizado',
    'foto_adjunto',
    'status_id',
    'obs_rechazado',
  ];

  const filters = [
    'id'          => '=',
    'empleado_id' => '=',
    'status_id'   => '=',
  ];

  const sorts = ['id', 'created_at'];

  /**
   * Mapa de columnas cuyo nombre difiere entre `rrhh_temp_data_persona` y
   * `rrhh_persona` (typo historico del legacy: `cel_refencia`).
   */
  const APPLICANT_COLUMN_MAP = [
    'cel_referencia' => 'cel_refencia',
  ];

  public function applicant()
  {
    return $this->belongsTo(Applicant::class, 'empleado_id')->withoutGlobalScopes();
  }
}
