<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApAssignBrandConsultantRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'year' => ['nullable', 'integer', 'digits:4', 'min:2000'],
      'month' => ['nullable', 'integer', 'between:1,12'],
      'sales_target' => ['nullable', 'integer', 'min:0'],
      'worker_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'brand_id' => ['nullable', 'integer', 'exists:ap_vehicle_brand,id'],
//      'company_branch_id' => ['nullable', 'integer', 'exists:config_sede,id'],
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'year.integer' => 'El campo año debe ser un número entero.',
      'year.digits' => 'El campo año debe tener exactamente 4 dígitos.',
      'year.min' => 'El campo año debe ser mayor o igual a 2000.',

      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.between' => 'El campo mes debe estar entre 1 y 12.',

      'sales_target.integer' => 'El campo objetivo de venta debe ser un número entero.',
      'sales_target.min' => 'El campo objetivo de venta debe ser mayor o igual a 0.',

      'worker_id.integer' => 'El campo asesor debe ser un número entero.',
      'worker_id.exists' => 'El asesor seleccionado no existe.',

      'brand_id.integer' => 'El campo marca debe ser un número entero.',
      'brand_id.exists' => 'La marca seleccionada no existe.',

//      'company_branch_id.integer' => 'El campo sede debe ser un número entero.',
//      'company_branch_id.exists' => 'La sede seleccionada no existe.',

      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
