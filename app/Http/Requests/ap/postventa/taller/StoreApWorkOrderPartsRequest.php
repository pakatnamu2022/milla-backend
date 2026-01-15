<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Illuminate\Validation\Validator;

class StoreApWorkOrderPartsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_order_id' => [
        'required',
        'integer',
        'exists:ap_work_orders,id',
      ],
      'group_number' => [
        'required',
        'integer',
        'min:1',
      ],
      'product_id' => [
        'required',
        'integer',
        'exists:products,id',
      ],
      'warehouse_id' => [
        'required',
        'integer',
        'exists:warehouse,id',
      ],
      'quantity_used' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'unit_cost' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'unit_price' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'discount_percentage' => [
        'nullable',
        'numeric',
        'min:0',
        'max:100',
      ],
      'subtotal' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'tax_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
      'total_amount' => [
        'nullable',
        'numeric',
        'min:0',
      ],
    ];
  }
  
  public function messages(): array
  {
    return [
      'work_order_id.required' => 'La orden de trabajo es obligatoria.',
      'work_order_id.integer' => 'La orden de trabajo debe ser un entero.',
      'work_order_id.exists' => 'La orden de trabajo seleccionada no es válida.',

      'group_number.required' => 'El número de grupo es obligatorio.',
      'group_number.integer' => 'El número de grupo debe ser un entero.',
      'group_number.min' => 'El número de grupo debe ser al menos 1.',

      'product_id.required' => 'El producto es obligatorio.',
      'product_id.integer' => 'El producto debe ser un entero.',
      'product_id.exists' => 'El producto seleccionado no es válido.',

      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.integer' => 'El almacén debe ser un entero.',
      'warehouse_id.exists' => 'El almacén seleccionado no es válido.',

      'quantity_used.required' => 'La cantidad utilizada es obligatoria.',
      'quantity_used.numeric' => 'La cantidad utilizada debe ser un número.',
      'quantity_used.min' => 'La cantidad utilizada debe ser mayor a 0.',

      'unit_cost.required' => 'El costo unitario es obligatorio.',
      'unit_cost.numeric' => 'El costo unitario debe ser un número.',
      'unit_cost.min' => 'El costo unitario no puede ser negativo.',

      'unit_price.required' => 'El precio unitario es obligatorio.',
      'unit_price.numeric' => 'El precio unitario debe ser un número.',
      'unit_price.min' => 'El precio unitario no puede ser negativo.',

      'discount_percentage.numeric' => 'El porcentaje de descuento debe ser un número.',
      'discount_percentage.min' => 'El porcentaje de descuento no puede ser negativo.',
      'discount_percentage.max' => 'El porcentaje de descuento no puede ser mayor a 100.',

      'subtotal.required' => 'El subtotal es obligatorio.',
      'subtotal.numeric' => 'El subtotal debe ser un número.',
      'subtotal.min' => 'El subtotal no puede ser negativo.',

      'tax_amount.numeric' => 'El monto de impuestos debe ser un número.',
      'tax_amount.min' => 'El monto de impuestos no puede ser negativo.',

      'total_amount.required' => 'El monto total es obligatorio.',
      'total_amount.numeric' => 'El monto total debe ser un número.',
      'total_amount.min' => 'El monto total no puede ser negativo.',
    ];
  }
}
