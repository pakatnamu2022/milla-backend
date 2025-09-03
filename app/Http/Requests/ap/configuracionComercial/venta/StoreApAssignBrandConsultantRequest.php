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

      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
      'marca_id' => ['required', 'integer', 'exists:ap_vehicle_brand,id'],

      'asesores' => ['required', 'array', 'min:1'],
      'asesores.*.asesor_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'asesores.*.objetivo' => ['required', 'integer', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'anio.required' => 'El año es obligatorio.',
      'anio.digits' => 'El año debe tener 4 dígitos.',
      'month.required' => 'El mes es obligatorio.',
      'month.between' => 'El mes debe estar entre 1 y 12.',

      'sede_id.required' => 'Debe seleccionar una sede.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'marca_id.required' => 'Debe seleccionar una marca.',
      'marca_id.exists' => 'La marca seleccionada no existe.',

      'asesores.required' => 'Debe agregar al menos un asesor.',
      'asesores.array' => 'El formato de asesores es inválido.',
      'asesores.*.asesor_id.required' => 'Cada asesor debe tener un ID.',
      'asesores.*.asesor_id.exists' => 'El asesor seleccionado no existe.',
      'asesores.*.objetivo.required' => 'Debe indicar el objetivo de cada asesor.',
      'asesores.*.objetivo.min' => 'El objetivo no puede ser negativo.',
    ];
  }
}
