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
      'objetivo_venta' => ['nullable', 'integer', 'min:0'],
      'asesor_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'marca_id' => ['nullable', 'integer', 'exists:ap_vehicle_brand,id'],
      'sede_id' => ['nullable', 'integer', 'exists:config_sede,id'],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'anio.integer' => 'El campo año debe ser un número entero.',
      'anio.digits' => 'El campo año debe tener exactamente 4 dígitos.',
      'anio.min' => 'El campo año debe ser mayor o igual a 2000.',
      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.between' => 'El campo mes debe estar entre 1 y 12.',
      'objetivo_venta.integer' => 'El campo objetivo de venta debe ser un número entero.',
      'objetivo_venta.min' => 'El campo objetivo de venta debe ser mayor o igual a 0.',
      'asesor_id.integer' => 'El campo asesor debe ser un número entero.',
      'asesor_id.exists' => 'El asesor seleccionado no existe.',
      'marca_id.integer' => 'El campo marca debe ser un número entero.',
      'marca_id.exists' => 'La marca seleccionada no existe.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
