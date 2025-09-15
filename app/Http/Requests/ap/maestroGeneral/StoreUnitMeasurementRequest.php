<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreUnitMeasurementRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'required',
        'string',
        'max:10',
        Rule::unique('unit_measurement', 'dyn_code')
          ->whereNull('deleted_at'),
      ],
      'nubefac_code' => [
        'required',
        'string',
        'max:10',
        Rule::unique('unit_measurement', 'nubefac_code')
          ->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:100',
        Rule::unique('unit_measurement', 'description')
          ->whereNull('deleted_at'),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.required' => 'El código DYN es obligatorio.',
      'dyn_code.string' => 'El código DYN debe ser una cadena de texto.',
      'dyn_code.max' => 'El código DYN no debe exceder los 10 caracteres.',
      'dyn_code.unique' => 'El código DYN ya existe.',

      'nubefac_code.required' => 'El código NUBEFACT es obligatorio.',
      'nubefac_code.string' => 'El código NUBEFACT debe ser una cadena de texto.',
      'nubefac_code.max' => 'El código NUBEFACT no debe exceder los 10 caracteres.',
      'nubefac_code.unique' => 'El código NUBEFACT ya existe.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 100 caracteres.',
      'description.unique' => 'La descripción ya existe.',
    ];
  }
}
