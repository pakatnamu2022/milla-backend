<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StoreAttendanceRuleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => ['required', 'string', 'max:10'],
      'description' => ['nullable', 'string', 'max:255'],
      'hour_type' => ['required', 'string', 'max:50'],
      'hours' => ['nullable', 'numeric:2', 'min:0', 'max:24'],
      'multiplier' => ['required', 'numeric', 'min:0'],
      'pay' => ['required', 'boolean'],
      'use_shift' => ['required', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'code' => 'código',
      'description' => 'descripción',
      'hour_type' => 'tipo de hora',
      'hours' => 'horas',
      'multiplier' => 'multiplicador',
      'pay' => 'pago',
      'use_shift' => 'usar turno',
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El :attribute es obligatorio.',
      'code.string' => 'El :attribute debe ser una cadena de texto.',
      'code.max' => 'El :attribute no debe exceder los 10 caracteres.',
      'description.string' => 'La :attribute debe ser una cadena de texto.',
      'description.max' => 'La :attribute no debe exceder los 255 caracteres.',
      'hour_type.required' => 'El :attribute es obligatorio.',
      'hour_type.string' => 'El :attribute debe ser una cadena de texto.',
      'hour_type.max' => 'El :attribute no debe exceder los 50 caracteres.',
      'hours.decimal' => 'El :attribute debe ser un número decimal con hasta 2 decimales.',
      'hours.min' => 'El :attribute no puede ser menor que 0.',
      'hours.max' => 'El :attribute no puede ser mayor que 24.',
      'multiplier.required' => 'El :attribute es obligatorio.',
      'multiplier.numeric' => 'El :attribute debe ser un número.',
      'multiplier.min' => 'El :attribute no puede ser menor que 0.',
      'pay.required' => 'El :attribute es obligatorio.',
      'pay.boolean' => 'El :attribute debe ser verdadero o falso.',
      'use_shift.required' => 'El :attribute es obligatorio.',
      'use_shift.boolean' => 'El :attribute debe ser verdadero o falso.',
    ];
  }
}
