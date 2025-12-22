<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'sometimes',
        'string',
        'max:255',
      ],
      'estimated_hours' => [
        'sometimes',
        'numeric',
        'min:0',
        'max:999.99',
      ],
      'planned_start_datetime' => [
        'sometimes',
        'nullable',
        'date',
      ],
      'planned_end_datetime' => [
        'sometimes',
        'nullable',
        'date',
        'after:planned_start_datetime',
      ],
      'status' => [
        'sometimes',
        'in:planned,in_progress,completed,canceled',
      ],
      'type' => [
        'nullable',
        'in:internal,external',
      ],
      'group_number' => [
        'required',
        'integer',
        'min:1',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.max' => 'La descripción no debe exceder 255 caracteres.',
      'estimated_hours.numeric' => 'Las horas estimadas deben ser un número.',
      'estimated_hours.min' => 'Las horas estimadas deben ser mayor o igual a 0.',
      'planned_start_datetime.date' => 'La fecha de inicio planificada debe ser una fecha válida.',
      'planned_end_datetime.date' => 'La fecha de fin planificada debe ser una fecha válida.',
      'planned_end_datetime.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
      'status.in' => 'El estado debe ser: planned, in_progress, completed o canceled.',
      'type.in' => 'El tipo debe ser: internal o external.',
      'group_number.required' => 'El número de grupo es obligatorio.',
      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'group_number.min' => 'El número de grupo debe ser al menos 1.',
    ];
  }
}
