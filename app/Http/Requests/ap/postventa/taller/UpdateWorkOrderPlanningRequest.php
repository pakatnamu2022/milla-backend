<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
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
    ];
  }

  public function messages(): array
  {
    return [
      'estimated_hours.numeric' => 'Las horas estimadas deben ser un número.',
      'estimated_hours.min' => 'Las horas estimadas deben ser mayor o igual a 0.',
      'planned_start_datetime.date' => 'La fecha de inicio planificada debe ser una fecha válida.',
      'planned_end_datetime.date' => 'La fecha de fin planificada debe ser una fecha válida.',
      'planned_end_datetime.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
    ];
  }
}
