<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApFuelTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_fuel_type', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('fuelType')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_fuel_type', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('fuelType')),
      ],
      'electric_motor' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 100 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ya está en uso.',

      'electric_motor.required' => 'El campo motor eléctrico es obligatorio.',
      'electric_motor.boolean' => 'El campo motor eléctrico debe ser verdadero o falso.',
    ];
  }
}
