<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'planned_start_datetime' => [
        'sometimes',
        'date',
      ],
      'planned_end_datetime' => [
        'sometimes',
        'date',
        'after:planned_start_datetime',
      ],
      'worker_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'planned_start_datetime.required' => 'La fecha de inicio planificada es requerida.',
      'planned_start_datetime.date' => 'La fecha de inicio planificada debe ser una fecha válida.',
      'planned_end_datetime.required' => 'La fecha de fin planificada es requerida.',
      'planned_end_datetime.date' => 'La fecha de fin planificada debe ser una fecha válida.',
      'planned_end_datetime.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
      'worker_id.required' => 'El trabajador es requerido.',
      'worker_id.integer' => 'El trabajador debe ser un número entero.',
      'worker_id.exists' => 'El trabajador seleccionado no existe.',
    ];
  }
}
