<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreWorkOrderLabourRequest extends StoreRequest
{
  protected function prepareForValidation()
  {
    // Convertir worker_id = 0 a null
    if ($this->has('worker_id') && $this->worker_id == 0) {
      $this->merge([
        'worker_id' => null,
      ]);
    }
  }

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
        function ($attribute, $value, $fail) {
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
        'required',
        'numeric',
        'min:0',
      ],
      'work_order_id' => [
        'required',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'worker_id' => [
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

      'worker_id.integer' => 'El trabajador debe ser un entero.',
      'worker_id.exists' => 'El trabajador seleccionado no existe.',

      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'group_number.min' => 'El número de grupo debe ser al menos 1.',
    ];
  }
}
