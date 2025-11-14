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
        Rule::unique('ap_warehouses', 'dyn_code')
          ->where('article_class_id', $this->article_class_id)
          ->ignore($this->route('warehouse')),
      ],
      'description' => [
        'nullable',
        'string',
        'max:100',
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
      'article_class_id' => [
        'nullable',
        'integer',
        'exists:ap_class_article,id',
      ],
      'is_received' => [
        'nullable',
        'boolean',
      ],
      'inventory_account' => [
        'nullable',
        'string',
        'max:50',
      ],
      'counterparty_account' => [
        'nullable',
        'string',
        'max:50',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El código dynamic debe ser un texto.',
      'dyn_code.max' => 'El código dynamic no puede tener más de 10 caracteres.',
      'dyn_code.unique' => 'El código dynamic ya está registrado para la clase de artículo seleccionada.',

      'description.string' => 'La descripción debe ser un texto.',
      'description.max' => 'La descripción no puede exceder los 100 caracteres.',
      'description.unique' => 'La descripción ya está registrada.',

      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionada no existe.',

      'article_class_id.integer' => 'La clase de artículo debe ser un número entero.',
      'article_class_id.exists' => 'La clase de artículo seleccionada no existe.',

      'is_received.boolean' => 'El campo de recibido debe ser verdadero o falso.',
    ];
  }
}
