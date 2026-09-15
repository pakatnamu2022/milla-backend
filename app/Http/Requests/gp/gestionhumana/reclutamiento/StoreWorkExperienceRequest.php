<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkExperienceRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'institucion'    => 'required|string|max:250',
      'cargo_ocupado'  => 'required|string|max:250',
      'motivo_cese'    => 'nullable|string|max:250',
      'telefono'       => 'nullable|string|max:20',
      'periodo'        => 'nullable|string|max:100',
      'jefe_directo'   => 'nullable|string|max:250',
      'cargo_jefe'     => 'nullable|string|max:250',
      'funciones'      => 'nullable|string',
    ];
  }
}
