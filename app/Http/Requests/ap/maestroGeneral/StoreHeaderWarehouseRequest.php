<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreHeaderWarehouseRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'required',
        'string',
        'max:50',
        Rule::unique('header_warehouses', 'dyn_code')
          ->whereNull('deleted_at'),
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
      ],
      'type_operation_id' => [
        'required',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'is_received' => [
        'required',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.required' => 'El campo código es obligatorio.',
      'dyn_code.string' => 'El código debe ser una cadena de texto.',
      'dyn_code.max' => 'El código no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El código ingresado ya existe en los registros.',

      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'type_operation_id.required' => 'El campo tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El campo tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no existe.',

      'is_received.required' => 'El campo recibido es obligatorio.',
      'is_received.boolean' => 'El campo recibido debe ser verdadero o falso.',
    ];
  }
}
