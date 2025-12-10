<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\IndexRequest;

class IndexWorkOrderItemRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      ...parent::rules(),
      'group_number' => [
        'nullable',
        'integer',
      ],
      'work_order_id' => [
        'nullable',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'type_planning_id' => [
        'nullable',
        'integer',
        'exists:ap_post_venta_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      ...parent::messages(),
      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'work_order_id.integer' => 'El ID de la orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',
      'type_planning_id.integer' => 'El tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',
    ];
  }
}