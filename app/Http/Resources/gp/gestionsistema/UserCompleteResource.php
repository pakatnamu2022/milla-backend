<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserCompleteResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $photoBase64 = null;

    if ($this->person?->foto_adjunto) {
      $path = $this->person->foto_adjunto;
      if (Storage::disk('general')->exists($path)) {
        $mime = Storage::disk('general')->mimeType($path);
        $content = Storage::disk('general')->get($path);
        $photoBase64 = "data:$mime;base64," . base64_encode($content);
      }
    }

    return [
      'id' => $this->id,
      'partner_id' => $this->partner_id,
      'username' => $this->username,
      'role' => $this->role?->nombre,

      // Person
      'name' => $this->person?->nombre_completo,
      'document' => $this->person?->vat,
      'license' => $this->person?->vat2,
      'passport' => $this->person?->vat3,
      'hazmat_license' => $this->person?->brevete_matpel,
      'license_class' => $this->person?->clase_brev,
      'license_category' => $this->person?->categoria_brev,
      'birth_date' => $this->person?->fecha_nacimiento,
      'nationality' => $this->person?->nacionalidad,
      'gender' => $this->person?->sexo,
      'education' => $this->person?->estudios?->nombre,

      'ubigeo' => $this->person?->ubigeo,
      'personal_email' => $this->person?->email,
      'personal_phone' => $this->person?->cel_personal,
      'reference_phone' => $this->person?->cel_referencia,
      'home_phone' => $this->person?->tel_referencia_2,
      'address' => $this->person?->direccion_principal,
      'address_reference' => $this->person?->direccion_ref,
      'district' => $this->person?->distrito,
      'province' => $this->person?->provincia,
      'department' => $this->person?->departamento,

      'children_count' => $this->person?->escolaridad,
      'birthplace' => $this->person?->lugar_nacimiento,
      'marital_status' => $this->person?->estado_civil,

      'primary_school' => $this->person?->centro_estudios_prim,
      'primary_school_status' => $this->person?->estado_estudios_prim,
      'secondary_school' => $this->person?->centro_estudios_sec,
      'secondary_school_status' => $this->person?->estado_estudios_sec,
      'technical_university' => $this->person?->institucion_tec_univ,
      'career' => $this->person?->carrera_tec_univ,
      'study_city' => $this->person?->ciudad_dep_est_tec_univ,
      'highest_degree' => $this->person?->nivel_alcanzado,
      'study_cycle' => $this->person?->ciclo_estudios,
      'study_years' => $this->person?->anos_curso,
      'degree_obtained' => $this->person?->grado_obtenido,

      'cv_file' => $this->person?->cv_adjunto, // aquí puedes poner download link si quieres
      'cv_last_update' => $this->person?->fecha_hora_ult_act_cv,

      // Job info
      'company' => $this->person?->sede?->company?->abbreviation,
      'branch' => $this->person?->sede?->suc_abrev,
      'position' => $this->person?->position?->name,
      'start_date' => $this->person?->fecha_inicio,

      // Media
      'photo' => $photoBase64,
    ];
  }
}
