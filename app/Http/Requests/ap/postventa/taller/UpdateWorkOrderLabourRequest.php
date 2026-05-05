<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UpdateWorkOrderLabourRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'description' => [
        'sometimes',
        'string',
        'max:500',
      ],
      'time_spent' => [
        'sometimes',
        function ($attribute, $value, $fail) {
          if ($value === null) {
            return;
          }

          // Validar si es formato decimal (horas)
          if (is_numeric($value)) {
            $hours = floatval($value);
            if ($hours <= 0 || $hours > 999) {
              $fail('El tiempo debe estar entre 0.01 y 999 horas.');
            }
            return;
          }

          // Validar si es formato HH:MM o HH:MM:SS
          if (preg_match('/^(\d{1,3}):([0-5]?\d)(:([0-5]?\d))?$/', $value, $matches)) {
            $hours = intval($matches[1]);
            $minutes = intval($matches[2]);

            if ($hours > 999) {
              $fail('Las horas no pueden ser mayores a 999.');
            }
            if ($minutes > 59) {
              $fail('Los minutos deben estar entre 0 y 59.');
            }
            if ($hours == 0 && $minutes == 0) {
              $fail('El tiempo debe ser mayor a 0.');
            }
            return;
          }

          $fail('El formato de tiempo debe ser decimal (ej: 2.5) o HH:MM / HH:MM:SS');
        }
      ],
      'hourly_rate' => [
        'sometimes',
        'numeric',
        'min:0',
      ],
      'discount_percentage' => [
        'sometimes',
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'work_order_id' => [
        'sometimes',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'worker_id' => [
        'sometimes',
        'nullable',
        'integer',
        'exists:rrhh_persona,id',
      ],
      'group_number' => [
        'sometimes',
        'integer',
        'min:1',
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

      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no existe.',

      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'group_number.min' => 'El número de grupo debe ser al menos 1.',
    ];
  }
}
