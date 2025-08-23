<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApVehicleColorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_color_vn', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleColor')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_color_vn', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleColor')),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.string' => 'El campo código debe ser una cadena de texto.',
      'codigo.max' => 'El campo código no debe exceder los 50 caracteres.',
      'codigo.unique' => 'El código ya está en uso.',

      'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
      'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ya está en uso.',
    ];
  }
}
