<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApAssignBrandConsultantRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'anio' => ['nullable', 'integer', 'digits:4', 'min:2000'],
      'month' => ['nullable', 'integer', 'between:1,12'],

      'idMarca' => ['nullable', 'integer', 'exists:ap_vehicle_brand,id'],

      'asesores' => ['nullable', 'array', 'min:1'],
      'asesores.*.idAsesor' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'asesores.*.objetivo' => ['nullable', 'integer', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'anio.digits' => 'El año debe tener 4 dígitos.',
      'month.between' => 'El mes debe estar entre 1 y 12.',

      'idMarca.exists' => 'La marca seleccionada no existe.',

      'asesores.array' => 'El formato de asesores es inválido.',
      'asesores.*.idAsesor.exists' => 'El asesor seleccionado no existe.',
      'asesores.*.objetivo.min' => 'El objetivo no puede ser negativo.',
    ];
  }
}
