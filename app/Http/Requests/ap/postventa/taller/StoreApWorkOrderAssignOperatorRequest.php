<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreApWorkOrderAssignOperatorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => [
        'required',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'group_number' => [
        'required',
        'integer',
      ],
      'operator_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
      'status' => [
        'nullable',
        'string',
        'max:50',
      ],
      'observations' => [
        'nullable',
        'string',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',

      'group_number.required' => 'El número de grupo es obligatorio.',
      'group_number.integer' => 'El número de grupo debe ser un entero.',

      'operator_id.required' => 'El operador es obligatorio.',
      'operator_id.integer' => 'El operador debe ser un entero.',
      'operator_id.exists' => 'El operador seleccionado no es válido.',

      'status.string' => 'El estado debe ser una cadena de texto.',
      'status.max' => 'El estado no debe exceder los 50 caracteres.',

      'observations.string' => 'Las observaciones deben ser una cadena de texto.',
    ];
  }
}
