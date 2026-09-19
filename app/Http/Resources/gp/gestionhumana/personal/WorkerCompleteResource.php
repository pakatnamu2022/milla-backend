<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Ficha completa de un trabajador (rrhh_persona). Misma forma que
 * UserCompleteResource (/perfil) para que el frontend reutilice el componente de perfil.
 * Los campos de cuenta (username, role) no aplican a un trabajador y salen en null.
 */
class WorkerCompleteResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'partner_id' => $this->id,
      'username' => null,
      'role' => null,
      ...self::profile($this->resource),
    ];
  }

  /** Campos de la persona compartidos con UserCompleteResource. */
  public static function profile(?Worker $person): array
  {
    $photoBase64 = null;

    if ($person?->foto_adjunto) {
      $path = $person->foto_adjunto;
      if (Storage::disk('general')->exists($path)) {
        $mime = Storage::disk('general')->mimeType($path);
        $content = Storage::disk('general')->get($path);
        $photoBase64 = "data:$mime;base64," . base64_encode($content);
      }
    }

    return [
      'name' => $person?->nombre_completo,
      'document' => $person?->vat,
      'license' => $person?->vat2,
      'passport' => $person?->vat3,
      'hazmat_license' => $person?->brevete_matpel,
      'license_class' => $person?->clase_brev,
      'license_category' => $person?->categoria_brev,
      'birth_date' => $person?->fecha_nacimiento,
      'nationality' => $person?->nacionalidad,
      'gender' => $person?->sexo,
      'education' => $person?->estudios?->nombre,

      'ubigeo' => $person?->ubigeo,
      'personal_email' => $person?->email,
      'personal_phone' => $person?->cel_personal,
      'reference_phone' => $person?->cel_referencia,
      'home_phone' => $person?->tel_referencia_2,
      'address' => $person?->direccion_principal,
      'address_reference' => $person?->direccion_ref,
      'district' => $person?->distrito,
      'province' => $person?->provincia,
      'department' => $person?->departamento,

      'children_count' => $person?->escolaridad,
      'birthplace' => $person?->lugar_nacimiento,
      'marital_status' => $person?->estado_civil,

      'primary_school' => $person?->centro_estudios_prim,
      'primary_school_status' => $person?->estado_estudios_prim,
      'secondary_school' => $person?->centro_estudios_sec,
      'secondary_school_status' => $person?->estado_estudios_sec,
      'technical_university' => $person?->institucion_tec_univ,
      'career' => $person?->carrera_tec_univ,
      'study_city' => $person?->ciudad_dep_est_tec_univ,
      'highest_degree' => $person?->nivel_alcanzado,
      'study_cycle' => $person?->ciclo_estudios,
      'study_years' => $person?->anos_curso,
      'degree_obtained' => $person?->grado_obtenido,

      'cv_file' => $person?->cv_adjunto,
      'cv_last_update' => $person?->fecha_hora_ult_act_cv,

      // Job info
      'company' => $person?->sede?->company?->abbreviation,
      'branch' => $person?->sede?->suc_abrev,
      'position' => $person?->position?->name,
      'start_date' => $person?->fecha_inicio,

      // Media
      'photo' => $photoBase64,
    ];
  }
}
