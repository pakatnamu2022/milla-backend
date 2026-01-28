<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use Illuminate\Contracts\Validation\Validator;

class StoreApSupplierOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'order_number' => [
        'required',
        'string',
        'unique:ap_supplier_order,order_number',
      ],
      'supplier_id' => [
        'required',
        'integer',
        'exists:business_partners,id',
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
      ],
      'warehouse_id' => [
        'required',
        'integer',
        'exists:warehouse,id',
      ],
      'type_currency_id' => [
        'required',
        'integer',
        'exists:type_currency,id',
      ],
      'order_date' => [
        'required',
        'date',
      ],
      'supply_type' => [
        'required',
        'string',
        'in:' . ApSupplierOrder::STOCK . ',' . ApSupplierOrder::LIMA . ',' . ApSupplierOrder::IMPORTACION,
      ],
      'is_take' => [
        'sometimes',
        'boolean',
      ],

      'request_detail_ids' => ['nullable', 'array'],

      // Details validation
      'details' => [
        'required',
        'array',
        'min:1',
      ],
      'details.*.product_id' => [
        'required',
        'integer',
        'exists:products,id',
      ],
      'details.*.unit_measurement_id' => [
        'required',
        'integer',
        'exists:unit_measurement,id',
      ],
      'details.*.note' => [
        'nullable',
        'string',
      ],
      'details.*.unit_price' => [
        'required',
        'numeric',
        'min:0',
      ],
      'details.*.quantity' => [
        'required',
        'numeric',
        'min:0.01',
      ],
      'details.*.total' => [
        'required',
        'numeric',
        'min:0',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'order_number.required' => 'El número de orden es obligatorio.',
      'order_number.string' => 'El número de orden debe ser una cadena de texto.',
      'order_number.unique' => 'El número de orden ya existe.',
      
      'supplier_id.required' => 'El proveedor es obligatorio.',
      'supplier_id.integer' => 'El proveedor debe ser un entero.',
      'supplier_id.exists' => 'El proveedor seleccionado no es válido.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',

      'warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouse_id.integer' => 'El almacén debe ser un entero.',
      'warehouse_id.exists' => 'El almacén seleccionado no es válido.',

      'type_currency_id.required' => 'El tipo de moneda es obligatorio.',
      'type_currency_id.integer' => 'El tipo de moneda debe ser un entero.',
      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'order_date.required' => 'La fecha de orden es obligatoria.',
      'order_date.date' => 'La fecha de orden debe ser una fecha válida.',

      'supply_type.required' => 'El tipo de suministro es obligatorio.',
      'supply_type.in' => 'El tipo de suministro debe ser: STOCK, LIMA o IMPORTACION.',

      'is_take.boolean' => 'El campo is_take debe ser verdadero o falso.',

      // Details messages
      'details.required' => 'Los detalles de la orden son obligatorios.',
      'details.array' => 'Los detalles deben ser un arreglo.',
      'details.min' => 'Debe incluir al menos un detalle en la orden.',

      'details.*.product_id.required' => 'El producto es obligatorio en cada detalle.',
      'details.*.product_id.integer' => 'El producto debe ser un entero.',
      'details.*.product_id.exists' => 'El producto seleccionado no es válido.',

      'details.*.unit_measurement_id.required' => 'La unidad de medida es obligatoria en cada detalle.',
      'details.*.unit_measurement_id.integer' => 'La unidad de medida debe ser un entero.',
      'details.*.unit_measurement_id.exists' => 'La unidad de medida seleccionada no es válida.',

      'details.*.note.string' => 'La nota debe ser una cadena de texto.',

      'details.*.unit_price.required' => 'El precio unitario es obligatorio en cada detalle.',
      'details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
      'details.*.unit_price.min' => 'El precio unitario debe ser mayor o igual a 0.',

      'details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
      'details.*.quantity.numeric' => 'La cantidad debe ser un número.',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',

      'details.*.total.required' => 'El total es obligatorio en cada detalle.',
      'details.*.total.numeric' => 'El total debe ser un número.',
      'details.*.total.min' => 'El total debe ser mayor o igual a 0.',
    ];
  }
}
