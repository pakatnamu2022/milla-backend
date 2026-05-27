<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateUnitMeasurementRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'nullable',
        'string',
        'max:20',
        Rule::unique('unit_measurement', 'dyn_code')
          ->whereNull('deleted_at')
          ->ignore($this->route('unitMeasurement')),
      ],
      'nubefac_code' => [
        'nullable',
        'string',
        'max:20',
        Rule::unique('unit_measurement', 'nubefac_code')
          ->whereNull('deleted_at')
          ->ignore($this->route('unitMeasurement')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('unit_measurement', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('unitMeasurement')),
      ],
      'number_decimals' => ['required', 'integer', 'max:5'],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El código DYN debe ser una cadena de texto.',
      'dyn_code.max' => 'El código DYN no debe exceder los 20 caracteres.',
      'dyn_code.unique' => 'El código DYN ya existe.',

      'nubefac_code.string' => 'El código NUBEFACT debe ser una cadena de texto.',
      'nubefac_code.max' => 'El código NUBEFACT no debe exceder los 20 caracteres.',
      'nubefac_code.unique' => 'El código NUBEFACT ya existe.',

      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 100 caracteres.',
      'description.unique' => 'La descripción ya existe.',

      'number_decimals.required' => 'El número de decimales es obligatorio.',
      'number_decimals.integer' => 'El número de decimales debe ser un número entero.',
      'number_decimals.max' => 'El número de decimales no debe exceder los 5 caracteres.',
    ];
  }
}
