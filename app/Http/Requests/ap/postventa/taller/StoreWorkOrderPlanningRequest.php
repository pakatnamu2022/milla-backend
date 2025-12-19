<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => [
        'required',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'worker_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
      'description' => [
        'required',
        'string',
        'max:255',
      ],
      'estimated_hours' => [
        'required',
        'numeric',
        'min:0',
        'max:999.99',
      ],
      'planned_start_datetime' => [
        'required',
        'date',
      ],
      'type' => [
        'nullable',
        'in:internal,external',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.exists' => 'La orden de trabajo no existe.',

      'worker_id.required' => 'El trabajador es obligatorio.',
      'worker_id.exists' => 'El trabajador no existe.',

      'description.required' => 'La descripción es obligatoria.',
      'description.max' => 'La descripción no debe exceder 255 caracteres.',

      'estimated_hours.required' => 'Las horas estimadas son obligatorias.',
      'estimated_hours.numeric' => 'Las horas estimadas deben ser un número.',
      'estimated_hours.min' => 'Las horas estimadas deben ser mayor o igual a 0.',

      'planned_start_datetime.required' => 'La fecha y hora de inicio planificada es obligatoria.',
      'planned_start_datetime.date' => 'La fecha de inicio planificada debe ser una fecha válida.',

      'type.in' => 'El tipo de planificación debe ser interno o externo.',
    ];
  }
}
