<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class UpdateProductShelfPositionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'product_warehouse_stock_id' => [
        'required',
        'integer',
        'exists:product_warehouse_stock,id',
      ],
      'product_shelf_id' => [
        'required',
        'integer',
        'exists:product_shelves,id',
      ],
      'position' => [
        'nullable',
        'string',
        'max:50',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'product_warehouse_stock_id.required' => 'El ID del stock es obligatorio.',
      'product_warehouse_stock_id.integer' => 'El ID del stock debe ser un número entero.',
      'product_warehouse_stock_id.exists' => 'El stock seleccionado no existe.',

      'product_shelf_id.required' => 'El estante es obligatorio.',
      'product_shelf_id.integer' => 'El estante debe ser un número entero.',
      'product_shelf_id.exists' => 'El estante seleccionado no existe.',

      'position.string' => 'La posición debe ser una cadena de texto.',
      'position.max' => 'La posición no debe exceder los 50 caracteres.',
    ];
  }
}