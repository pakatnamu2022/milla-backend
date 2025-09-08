<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApAssignCompanyBranchRequest extends FormRequest
{
  public function rules(): array
  {
    return [
      'year' => 'required|integer|min:2000|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'company_branch_id' => 'required|exists:company_branch,id',
      'workers' => 'required|array|min:1',
      'workers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'year.required' => 'El campo year es obligatorio.',
      'year.integer' => 'El campo year debe ser un número entero.',
      'year.min' => 'El campo year debe ser al menos 2000.',
      'year.max' => 'El campo year no debe ser mayor que 2100.',
      'month.required' => 'El campo month es obligatorio.',
      'month.integer' => 'El campo month debe ser un número entero.',
      'month.min' => 'El campo month debe ser al menos 1.',
      'month.max' => 'El campo month no debe ser mayor que 12.',
      'company_branch_id.required' => 'El campo company_branch_id es obligatorio.',
      'company_branch_id.exists' => 'La sede proporcionada no existe.',
      'workers.required' => 'El campo workers es obligatorio.',
      'workers.array' => 'El campo workers debe ser un arreglo.',
      'workers.min' => 'Debe proporcionar al menos un trabajador.',
      'workers.*.integer' => 'Cada trabajador debe ser un ID entero válido.',
      'workers.*.exists' => 'Uno o más IDs de trabajadores proporcionados no existen.',
    ];
  }
}
