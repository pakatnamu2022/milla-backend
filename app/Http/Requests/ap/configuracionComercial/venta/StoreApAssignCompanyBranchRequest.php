<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreApAssignCompanyBranchRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      //'company_branch_id' => 'required|exists:company_branch,id',
      'sede_id' => 'required|exists:config_sede,id',
      'workers' => 'required|array|min:1',
      'workers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'sede_id.required' => 'El campo sede_id es obligatorio.',
      'sede_id.exists' => 'La sede proporcionada no existe.',

//      'company_branch_id.required' => 'El campo company_branch_id es obligatorio.',
//      'company_branch_id.exists' => 'La sede proporcionada no existe.',

      'workers.required' => 'El campo workers es obligatorio.',
      'workers.array' => 'El campo workers debe ser un arreglo.',
      'workers.min' => 'Debe proporcionar al menos un trabajador.',

      'workers.*.integer' => 'Cada trabajador debe ser un ID entero válido.',
      'workers.*.exists' => 'Uno o más IDs de trabajadores proporcionados no existen.',
    ];
  }
}
