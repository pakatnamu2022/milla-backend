<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApAssignBrandConsultantRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => ['required', 'integer', 'digits:4', 'min:2000'],
      'month' => ['required', 'integer', 'between:1,12'],
      'sales_target' => ['required', 'integer', 'min:0'],
      'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'brand_id' => ['required', 'integer', 'exists:ap_vehicle_brand,id'],
//      'company_branch_id' => ['required', 'integer', 'exists:config_sede,id'],
      'sede_id' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'year.required' => 'El campo año es obligatorio.',
      'year.integer' => 'El campo año debe ser un número entero.',
      'year.digits' => 'El campo año debe tener exactamente 4 dígitos.',
      'year.min' => 'El campo año debe ser mayor o igual a 2000.',

      'month.required' => 'El campo mes es obligatorio.',
      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.between' => 'El campo mes debe estar entre 1 y 12.',

      'sales_target.required' => 'El campo objetivo de venta es obligatorio.',
      'sales_target.integer' => 'El campo objetivo de venta debe ser un número entero.',
      'sales_target.min' => 'El campo objetivo de venta debe ser mayor o igual a 0.',

      'worker_id.required' => 'El campo asesor es obligatorio.',
      'worker_id.integer' => 'El campo asesor debe ser un número entero.',
      'worker_id.exists' => 'El asesor seleccionado no existe.',

      'brand_id.required' => 'El campo marca es obligatorio.',
      'brand_id.integer' => 'El campo marca debe ser un número entero.',
      'brand_id.exists' => 'La marca seleccionada no existe.',
      
//      'company_branch_id.required' => 'El campo sede es obligatorio.',
//      'company_branch_id.integer' => 'El campo sede debe ser un número entero.',
//      'company_branch_id.exists' => 'La sede seleccionada no existe.',

      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
