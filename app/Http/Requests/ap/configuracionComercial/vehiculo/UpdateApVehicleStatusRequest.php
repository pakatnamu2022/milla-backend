<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApVehicleStatusRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_vehicle_status', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleStatus')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_vehicle_status', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('vehicleStatus')),
      ],
      'use' => [
        'nullable',
        Rule::in(['VENTA', 'TALLER']),
      ],
      'color' => [
        'nullable',
        'string',
        'max:20',
      ],
      'status' => ['nullable', 'boolean']
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 50 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
      'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'La descripción ya está en uso.',

      'use.in' => 'El campo uso debe ser VENTA o TALLER.',

      'color.string' => 'El campo color debe ser una cadena de texto.',
      'color.max' => 'El campo color no debe exceder los 20 caracteres.',
    ];
  }
}
