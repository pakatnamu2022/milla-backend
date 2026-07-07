<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateWorkOrderItemsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'id' => [
        'required',
        'integer',
        'exists:ap_work_order_items,id',
      ],
      'type_planning_id' => [
        'required',
        'integer',
        Rule::exists('type_planning_work_order', 'id'),
      ],
      'type_operation_id' => [
        'required',
        'integer',
        Rule::exists('ap_masters', 'id')
          ->where('type', 'TIPO_OPERACION_CITA'),
      ],
      'description' => [
        'required',
        'string',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'id.required' => 'El ID del ítem es obligatorio.',
      'id.integer' => 'El ID del ítem debe ser un entero.',
      'id.exists' => 'El ítem seleccionado no es válido.',

      'type_planning_id.required' => 'El tipo de planificación es obligatorio.',
      'type_planning_id.integer' => 'El tipo de planificación debe ser un entero.',
      'type_planning_id.exists' => 'El tipo de planificación seleccionado no es válido.',

      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El tipo de operación debe ser un entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'description.required' => 'La descripción del ítem es obligatoria.',
      'description.string' => 'La descripción del ítem debe ser una cadena de texto.',
    ];
  }
}