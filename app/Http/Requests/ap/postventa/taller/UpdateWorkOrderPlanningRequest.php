<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\UpdateRequest;

class UpdateWorkOrderPlanningRequest extends UpdateRequest
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
    ];
  }
}