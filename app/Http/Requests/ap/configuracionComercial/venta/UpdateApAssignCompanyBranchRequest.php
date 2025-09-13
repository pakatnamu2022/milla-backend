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
      'sede_id' => 'required|exists:config_sede,id',
      'assigned_workers' => 'required|array|min:1',
      'assigned_workers.*' => 'integer|exists:rrhh_persona,id',
      'status' => 'nullable|boolean',
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

      'sede_id.required' => 'El campo sede_id es obligatorio.',
      'sede_id.exists' => 'La sede proporcionada no existe.',

      'assigned_workers.required' => 'El campo asesores es obligatorio.',
      'assigned_workers.array' => 'El campo asesores debe ser un arreglo.',
      'assigned_workers.min' => 'Debe proporcionar al menos un asesor.',

      'assigned_workers.*.integer' => 'Cada asesor debe ser un ID entero válido.',
      'assigned_workers.*.exists' => 'Uno o más IDs de asesores proporcionados no existen.',
    ];
  }
}
