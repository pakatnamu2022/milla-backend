<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantDataChangeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                     => $this->id,
      'empleado_id'            => $this->empleado_id,
      'applicant'              => $this->whenLoaded('applicant', fn() => $this->applicant ? [
        'id'              => $this->applicant->id,
        'nombre_completo' => $this->applicant->nombre_completo,
        'vat'             => $this->applicant->vat,
      ] : null),
      'nombre_completo'         => $this->nombre_completo,
      'vat'                     => $this->vat,
      'vat2'                    => $this->vat2,
      'vat3'                    => $this->vat3,
      'brevete_matpel'          => $this->brevete_matpel,
      'clase_brev'              => $this->clase_brev,
      'categoria_brev'          => $this->categoria_brev,
      'fecha_nacimiento'        => $this->fecha_nacimiento,
      'nacionalidad'            => $this->nacionalidad,
      'estudios_id'             => $this->estudios_id,
      'ubigeo'                  => $this->ubigeo,
      'email'                   => $this->email,
      'cel_personal'            => $this->cel_personal,
      'cel_referencia'          => $this->cel_referencia,
      'tel_referencia_2'        => $this->tel_referencia_2,
      'direccion_principal'     => $this->direccion_principal,
      'direccion_ref'           => $this->direccion_ref,
      'distrito'                => $this->distrito,
      'provincia'               => $this->provincia,
      'departamento'            => $this->departamento,
      'escolaridad'             => $this->escolaridad,
      'lugar_nacimiento'        => $this->lugar_nacimiento,
      'estado_civil'            => $this->estado_civil,
      'fecha_estado_civil'      => $this->fecha_estado_civil,
      'estado_estudios_prim'    => $this->estado_estudios_prim,
      'centro_estudios_prim'    => $this->centro_estudios_prim,
      'estado_estudios_sec'     => $this->estado_estudios_sec,
      'centro_estudios_sec'     => $this->centro_estudios_sec,
      'institucion_tec_univ'    => $this->institucion_tec_univ,
      'carrera_tec_univ'        => $this->carrera_tec_univ,
      'ciudad_dep_est_tec_univ' => $this->ciudad_dep_est_tec_univ,
      'nivel_alcanzado'         => $this->nivel_alcanzado,
      'ciclo_estudios'          => $this->ciclo_estudios,
      'anos_curso'              => $this->anos_curso,
      'grado_obtenido'          => $this->grado_obtenido,
      'proceso_postulacion_id'  => $this->proceso_postulacion_id,
      'sexo'                    => $this->sexo,
      'cv_actualizado'          => $this->cv_actualizado,
      'foto_adjunto'            => $this->foto_adjunto,
      'status_id'               => $this->status_id,
      'obs_rechazado'           => $this->obs_rechazado,
      'created_at'              => $this->created_at,
      'updated_at'              => $this->updated_at,
    ];
  }
}
