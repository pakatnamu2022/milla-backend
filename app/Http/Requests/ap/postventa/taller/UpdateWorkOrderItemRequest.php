<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderItemRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => [
        'nullable',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'group_number' => [
        'nullable',
        'integer',
        'min:1',
      ],
      'type_planning_id' => [
        'nullable',
        'integer',
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_PLANIFICACION'),
      ],
      'description' => [
        'nullable',
        'string',
        'max:500',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',

      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'group_number.min' => 'El número de grupo debe ser al menos 1.',

      'type_planning_id.integer' => 'El tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',
    ];
  }
}
