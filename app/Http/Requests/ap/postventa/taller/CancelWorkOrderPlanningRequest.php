<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class CancelWorkOrderPlanningRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'actual_end_datetime' => [
        'required',
        'date',
      ],
      'canceled_note' => [
        'required',
        'string',
        'max:500',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'actual_end_datetime.required' => 'La fecha y hora de finalización es requerida.',
      'actual_end_datetime.date' => 'La fecha y hora de finalización debe ser una fecha válida.',
      'canceled_note.required' => 'La nota de cancelación es requerida.',
      'canceled_note.string' => 'La nota de cancelación debe ser texto.',
      'canceled_note.max' => 'La nota de cancelación no puede exceder 500 caracteres.',
    ];
  }
}