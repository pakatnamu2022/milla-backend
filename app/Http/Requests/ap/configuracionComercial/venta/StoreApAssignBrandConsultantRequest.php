<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApAssignBrandConsultantRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'anio' => ['required', 'integer', 'digits:4', 'min:2000'],
      'month' => ['required', 'integer', 'between:1,12'],
      'objetivo_venta' => ['required', 'integer', 'min:0'],
      'asesor_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'marca_id' => ['required', 'integer', 'exists:ap_vehicle_brand,id'],
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'anio.required' => 'El campo año es obligatorio.',
      'anio.integer' => 'El campo año debe ser un número entero.',
      'anio.digits' => 'El campo año debe tener exactamente 4 dígitos.',
      'anio.min' => 'El campo año debe ser mayor o igual a 2000.',
      'month.required' => 'El campo mes es obligatorio.',
      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.between' => 'El campo mes debe estar entre 1 y 12.',
      'objetivo_venta.required' => 'El campo objetivo de venta es obligatorio.',
      'objetivo_venta.integer' => 'El campo objetivo de venta debe ser un número entero.',
      'objetivo_venta.min' => 'El campo objetivo de venta debe ser mayor o igual a 0.',
      'asesor_id.required' => 'El campo asesor es obligatorio.',
      'asesor_id.integer' => 'El campo asesor debe ser un número entero.',
      'asesor_id.exists' => 'El asesor seleccionado no existe.',
      'marca_id.required' => 'El campo marca es obligatorio.',
      'marca_id.integer' => 'El campo marca debe ser un número entero.',
      'marca_id.exists' => 'La marca seleccionada no existe.',
      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
