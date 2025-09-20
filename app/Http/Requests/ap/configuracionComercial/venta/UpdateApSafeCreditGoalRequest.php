<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class UpdateApSafeCreditGoalRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => [
        'sometimes',
        'integer',
        'min:2000',
        'max:2100'
      ],
      'month' => [
        'sometimes',
        'integer',
        'min:1',
        'max:12'
      ],
      'goal_amount' => [
        'sometimes',
        'numeric',
        'min:1'
      ],
      'type' => [
        'sometimes',
        'string',
        'in:CREDITO,SEGURO'
      ],
      'sede_id' => [
        'sometimes',
        'integer',
        'exists:config_sede,id'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'year.integer' => 'El campo año debe ser un número entero.',
      'year.min' => 'El campo año debe ser mayor o igual a 2000.',
      'year.max' => 'El campo año debe ser menor o igual a 2100.',

      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.min' => 'El campo mes debe ser mayor o igual a 1.',
      'month.max' => 'El campo mes debe ser menor o igual a 12.',

      'goal_amount.numeric' => 'El campo monto meta debe ser un número.',
      'goal_amount.min' => 'El campo monto meta debe ser mayor o igual a 1.',

      'type.string' => 'El campo tipo debe ser una cadena de texto.',
      'type.in' => 'El campo tipo debe ser CREDITO o SEGURO.',

      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
    ];
  }
}
