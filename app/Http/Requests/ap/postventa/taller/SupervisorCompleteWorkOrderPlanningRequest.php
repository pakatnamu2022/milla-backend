<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class SupervisorCompleteWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'end_datetime' => [
        'required',
        'date',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'end_datetime.required' => 'La fecha y hora de fin es requerida.',
      'end_datetime.date' => 'La fecha y hora de fin debe ser una fecha válida.',
    ];
  }
}