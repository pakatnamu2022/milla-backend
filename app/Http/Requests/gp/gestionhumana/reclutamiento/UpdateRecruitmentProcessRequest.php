<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecruitmentProcessRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre_postulacion' => 'sometimes|required|string|max:250',
      'cant_trab_solicita' => 'sometimes|required|integer|min:1',
      'sede_id'            => 'sometimes|required|integer|exists:config_sede,id',
      'area_id'            => 'sometimes|required|integer|exists:rrhh_area,id',
      'cargo_id'           => 'sometimes|required|integer|exists:rrhh_cargo,id',
      'fecha_inicio'       => 'sometimes|required|date_format:Y-m-d',
    ];
  }
}
