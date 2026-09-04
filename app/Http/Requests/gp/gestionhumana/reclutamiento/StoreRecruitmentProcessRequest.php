<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecruitmentProcessRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'nombre_postulacion' => 'required|string|max:250',
      'cant_trab_solicita' => 'required|integer|min:1',
      'sede_id'            => 'required|integer|exists:config_sede,id',
      'area_id'            => 'required|integer|exists:rrhh_area,id',
      'cargo_id'           => 'required|integer|exists:rrhh_cargo,id',
      'fecha_inicio'       => 'required|date_format:Y-m-d',
    ];
  }
}
