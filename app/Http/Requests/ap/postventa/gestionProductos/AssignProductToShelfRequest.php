<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class AssignProductToShelfRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'product_shelf_id' => [
        'required',
        'integer',
        'exists:product_shelves,id',
      ],
      'products' => [
        'required',
        'array',
        'min:1',
      ],
      'products.*.product_warehouse_stock_id' => [
        'required',
        'integer',
        'exists:product_warehouse_stock,id',
      ],
      'products.*.position' => [
        'nullable',
        'string',
        'max:50',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'product_shelf_id.required' => 'El estante es obligatorio.',
      'product_shelf_id.integer' => 'El estante debe ser un número entero.',
      'product_shelf_id.exists' => 'El estante seleccionado no existe.',

      'products.required' => 'Debe especificar al menos un producto.',
      'products.array' => 'Los productos deben ser un arreglo.',
      'products.min' => 'Debe especificar al menos un producto.',

      'products.*.product_warehouse_stock_id.required' => 'El ID del stock es obligatorio.',
      'products.*.product_warehouse_stock_id.integer' => 'El ID del stock debe ser un número entero.',
      'products.*.product_warehouse_stock_id.exists' => 'El stock seleccionado no existe.',

      'products.*.position.string' => 'La posición debe ser una cadena de texto.',
      'products.*.position.max' => 'La posición no debe exceder los 50 caracteres.',
    ];
  }
}