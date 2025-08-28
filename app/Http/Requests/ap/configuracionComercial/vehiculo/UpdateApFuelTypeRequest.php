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
      'codigo' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_fuel_type', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('fuelType')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_fuel_type', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('fuelType')),
      ],
      'motor_electrico' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.required' => 'El campo código es obligatorio.',
      'codigo.string' => 'El campo código debe ser una cadena de texto.',
      'codigo.max' => 'El campo código no debe exceder los 100 caracteres.',
      'codigo.unique' => 'El código ya está en uso.',

      'descripcion.required' => 'El campo descripción es obligatorio.',
      'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
      'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ya está en uso.',

      'motor_electrico.required' => 'El campo motor eléctrico es obligatorio.',
      'motor_electrico.boolean' => 'El campo motor eléctrico debe ser verdadero o falso.',
    ];
  }
}
