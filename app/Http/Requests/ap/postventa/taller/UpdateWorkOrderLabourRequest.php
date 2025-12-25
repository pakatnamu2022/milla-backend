<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateWorkOrderLabourRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'nullable',
        'string',
        'max:500',
      ],
      'time_spent' => [
        'nullable',
        'numeric',
        'min:0.01',
      ],
      'hourly_rate' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'work_order_id' => [
        'nullable',
        'integer',
        'exists:ap_work_orders,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',

      'time_spent.numeric' => 'El tiempo empleado debe ser un número.',
      'time_spent.min' => 'El tiempo empleado debe ser mayor a 0.',

      'hourly_rate.numeric' => 'La tarifa por hora debe ser un número.',
      'hourly_rate.min' => 'La tarifa por hora no puede ser negativa.',

      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no existe.',
    ];
  }
}
