<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreWorkOrderItemRequest extends StoreRequest
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
        'min:1',
      ],
      'type_planning_id' => [
        'required',
        'integer',
        Rule::exists('ap_post_venta_masters', 'id')
          ->where('type', 'TIPO_PLANIFICACION'),
      ],
      'description' => [
        'required',
        'string',
        'max:500',
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
      'group_number.min' => 'El número de grupo debe ser al menos 1.',

      'type_planning_id.required' => 'El tipo de planificación es obligatorio.',
      'type_planning_id.integer' => 'El tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',
    ];
  }
}