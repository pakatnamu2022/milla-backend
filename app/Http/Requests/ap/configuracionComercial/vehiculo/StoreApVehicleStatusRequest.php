<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApVehicleStatusRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:50',
        Rule::unique('ap_vehicle_status', 'code')->whereNull('deleted_at'),
      ],
      'description' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_vehicle_status', 'description')->whereNull('deleted_at'),
      ],
      'use' => [
        'required',
        Rule::in(['VENTA', 'TALLER']),
      ],
      'color' => [
        'required',
        'string',
        'max:20',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 50 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'description.required' => 'El campo descripción es obligatorio.',
      'description.string' => 'El campo descripción debe ser una cadena de texto.',
      'description.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'description.unique' => 'La descripción ya está en uso.',

      'use.required' => 'El campo uso es obligatorio.',
      'use.in' => 'El campo uso debe ser VENTA o TALLER.',
      
      'color.required' => 'El campo color es obligatorio.',
      'color.string' => 'El campo color debe ser una cadena de texto.',
      'color.max' => 'El campo color no debe exceder los 20 caracteres.',
    ];
  }
}
