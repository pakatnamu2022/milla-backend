<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRuleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'string',
        'max:10',
      ],
      'hour_type' => ['sometimes', 'string', 'max:50'],
      'hours' => ['nullable', 'numeric:2', 'min:0', 'max:24'],
      'multiplier' => ['sometimes', 'numeric', 'min:0'],
      'pay' => ['sometimes', 'boolean'],
      'use_shift' => ['sometimes', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'code' => 'código',
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
      'code.string' => 'El :attribute debe ser una cadena de texto.',
      'code.max' => 'El :attribute no debe exceder los 10 caracteres.',
      'hour_type.string' => 'El :attribute debe ser una cadena de texto.',
      'hour_type.max' => 'El :attribute no debe exceder los 50 caracteres.',
      'hours.decimal' => 'El :attribute debe ser un número decimal con hasta 2 decimales.',
      'hours.min' => 'El :attribute no puede ser menor que 0.',
      'hours.max' => 'El :attribute no puede ser mayor que 24.',
      'multiplier.numeric' => 'El :attribute debe ser un número.',
      'multiplier.min' => 'El :attribute no puede ser menor que 0.',
      'pay.boolean' => 'El :attribute debe ser verdadero o falso.',
      'use_shift.boolean' => 'El :attribute debe ser verdadero o falso.',
    ];
  }
}
