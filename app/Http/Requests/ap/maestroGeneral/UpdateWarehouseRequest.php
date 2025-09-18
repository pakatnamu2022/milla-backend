<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'dyn_code' => [
        'nullable',
        'string',
        'max:10',
        Rule::unique('warehouse', 'dyn_code')
          ->whereNull('deleted_at')
          ->ignore($this->route('warehouse')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('warehouse', 'description')
          ->whereNull('deleted_at')
          ->ignore($this->route('warehouse')),
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
      'status' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El código dinámico debe ser un texto.',
      'dyn_code.max' => 'El código dinámico no puede tener más de 10 caracteres.',
      'dyn_code.unique' => 'El código dinámico ya existe en el sistema.',

      'description.string' => 'La descripción debe ser un texto.',
      'description.max' => 'La descripción no puede exceder los 100 caracteres.',
      'description.unique' => 'La descripción ya está registrada.',

      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionada no existe.',
    ];
  }
}
