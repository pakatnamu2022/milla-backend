<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateHeaderWarehouseRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('header_warehouses', 'dyn_code')
          ->whereNull('deleted_at')
          ->ignore($this->route('headerWarehouse')),
      ],
      'sede_id' => [
        'nullable',
        'integer',
        'exists:config_sede,id',
      ],
      'type_operation_id' => [
        'nullable',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'is_received' => [
        'nullable',
        'boolean',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El código debe ser una cadena de texto.',
      'dyn_code.max' => 'El código no debe exceder los 50 caracteres.',
      'dyn_code.unique' => 'El código ingresado ya existe en los registros.',

      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'type_operation_id.integer' => 'El campo tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no existe.',

      'is_received.boolean' => 'El campo recibido debe ser verdadero o falso.',

      'status.boolean' => 'El campo estado debe ser verdadero o falso.',
    ];
  }
}
