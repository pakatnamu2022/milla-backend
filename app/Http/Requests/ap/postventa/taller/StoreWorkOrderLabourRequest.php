<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreWorkOrderLabourRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'required',
        'string',
        'max:500',
      ],
      'time_spent' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'hourly_rate' => [
        'required',
        'numeric',
        'min:0',
      ],
      'work_order_id' => [
        'required',
        'integer',
        'exists:ap_work_orders,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'description.max' => 'La descripción no debe exceder los 500 caracteres.',

      'time_spent.required' => 'El tiempo empleado es obligatorio.',
      'time_spent.numeric' => 'El tiempo empleado debe ser un número.',
      'time_spent.min' => 'El tiempo empleado debe ser mayor a 0.',

      'hourly_rate.required' => 'La tarifa por hora es obligatoria.',
      'hourly_rate.numeric' => 'La tarifa por hora debe ser un número.',
      'hourly_rate.min' => 'La tarifa por hora no puede ser negativa.',

      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no existe.',
    ];
  }
}
