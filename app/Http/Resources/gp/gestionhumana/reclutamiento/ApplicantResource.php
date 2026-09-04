<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                     => $this->id,
      'nombre_completo'        => $this->nombre_completo,
      'vat'                    => $this->vat,
      'vat2'                   => $this->vat2,
      'vat3'                   => $this->vat3,
      'sexo'                   => $this->sexo,
      'fecha_nacimiento'       => $this->fecha_nacimiento?->format('Y-m-d'),
      'estado_civil'           => $this->estado_civil,
      'nacionalidad'           => $this->nacionalidad,
      'lugar_nacimiento'       => $this->lugar_nacimiento,
      'email'                  => $this->email,
      'cel_personal'           => $this->cel_personal,
      'cel_refencia'           => $this->cel_refencia,
      'tel_referencia_2'       => $this->tel_referencia_2,
      'direccion_principal'    => $this->direccion_principal,
      'direccion_ref'          => $this->direccion_ref,
      'distrito'               => $this->distrito,
      'provincia'              => $this->provincia,
      'departamento'           => $this->departamento,
      'brevete_matpel'         => $this->brevete_matpel,
      'clase_brev'             => $this->clase_brev,
      'categoria_brev'         => $this->categoria_brev,
      'estudios_id'            => $this->estudios_id,
      'escolaridad'            => $this->escolaridad,
      'estado_estudios_prim'   => $this->estado_estudios_prim,
      'centro_estudios_prim'   => $this->centro_estudios_prim,
      'estado_estudios_sec'    => $this->estado_estudios_sec,
      'centro_estudios_sec'    => $this->centro_estudios_sec,
      'institucion_tec_univ'   => $this->institucion_tec_univ,
      'carrera_tec_univ'       => $this->carrera_tec_univ,
      'ciudad_dep_est_tec_univ' => $this->ciudad_dep_est_tec_univ,
      'nivel_alcanzado'        => $this->nivel_alcanzado,
      'ciclo_estudios'         => $this->ciclo_estudios,
      'anos_curso'             => $this->anos_curso,
      'grado_obtenido'         => $this->grado_obtenido,
      'cv_actualizado'         => $this->cv_actualizado,
      'foto_adjunto'           => $this->foto_adjunto,
      'sede_id'                => $this->sede_id,
      'sede'                   => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura ?? $this->sede?->name),
      'area_id'                => $this->area_id,
      'area'                   => $this->whenLoaded('area', fn() => $this->area?->name),
      'cargo_id'               => $this->cargo_id,
      'cargo'                  => $this->whenLoaded('position', fn() => $this->position?->name),
      'centro_costo_id'        => $this->centro_costo_id,
      'proceso_postulacion_id' => $this->proceso_postulacion_id,
      'proceso'                => $this->whenLoaded('process', fn() => $this->process?->nombre_postulacion),
      'tipo_trabajador_id'     => $this->tipo_trabajador_id,
      'estado_postulante'      => self::labelForType((int) $this->tipo_trabajador_id),
      'jefe_id'                => $this->jefe_id,
      'motivo_status'          => $this->motivo_status,
      'has_user'               => $this->whenLoaded('user', fn() => $this->user !== null),
      'created_at'             => $this->created_at,
      'updated_at'             => $this->updated_at,
    ];
  }

  private static function labelForType(int $type): string
  {
    return match ($type) {
      Applicant::TIPO_POSTULANTE   => 'Postulante',
      Applicant::TIPO_CONTRATADO   => 'Contratado',
      Applicant::TIPO_RECHAZADO    => 'Rechazado',
      Applicant::TIPO_FUERA_CUPO   => 'Fuera de cupo',
      Applicant::TIPO_LISTA_NEGRA  => 'Lista negra',
      Applicant::TIPO_SELECCIONADO => 'Seleccionado',
      default                      => 'Postulante',
    };
  }
}
