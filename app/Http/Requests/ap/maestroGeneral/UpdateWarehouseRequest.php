<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends StoreRequest
{
  public function rules(): array
  {
    $rules = [
      'dyn_code' => [
        'nullable',
        'string',
        'max:10',
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
      'parent_warehouse_id' => [
        'nullable',
        'integer',
        'exists:warehouse,id',
      ],
      'is_physical_warehouse' => [
        'nullable',
        'boolean',
      ],
    ];

    // Validar unicidad de la combinación [dyn_code, article_class_id, sede_id]
    $rules['dyn_code'][] = Rule::unique('warehouse', 'dyn_code')
      ->where('article_class_id', $this->article_class_id)
      ->where('sede_id', $this->sede_id)
      ->ignore($this->route('warehouse'));

    return $rules;
  }

  public function messages(): array
  {
    return [
      'dyn_code.string' => 'El código dynamic debe ser un texto.',
      'dyn_code.max' => 'El código dynamic no puede tener más de 10 caracteres.',
      'dyn_code.unique' => 'Ya existe un almacén con esta combinación de código, clase de artículo y sede.',
      'description.string' => 'La descripción debe ser un texto.',
      'description.max' => 'La descripción no puede exceder los 100 caracteres.',
      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.exists' => 'El tipo de operación seleccionada no existe.',
      'article_class_id.integer' => 'La clase de artículo debe ser un número entero.',
      'article_class_id.exists' => 'La clase de artículo seleccionada no existe.',
      'is_received.boolean' => 'El campo de recibido debe ser verdadero o falso.',
      'inventory_account.string' => 'La cuenta de inventario debe ser un texto.',
      'counterparty_account.string' => 'La cuenta de contrapartida debe ser un texto.',
      'counterparty_account.max' => 'La cuenta de contrapartida no puede exceder los 50 caracteres.',
      'parent_warehouse_id.integer' => 'El almacén padre debe ser un número entero.',
      'parent_warehouse_id.exists' => 'El almacén padre seleccionado no existe.',
      'is_physical_warehouse.boolean' => 'El campo de almacén físico debe ser verdadero o falso.',
    ];
  }
}
