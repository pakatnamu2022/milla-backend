<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreProductsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:50',
        Rule::unique('products', 'code')->whereNull('deleted_at'),
      ],
      'dyn_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('products', 'dyn_code')->whereNull('deleted_at'),
      ],
      'nubefac_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('products', 'nubefac_code')->whereNull('deleted_at'),
      ],
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'product_category_id' => 'required|exists:product_category,id',
      'brand_id' => 'nullable|exists:ap_vehicle_brand,id',
      'unit_measurement_id' => 'required|exists:unit_measurement,id',
      'ap_class_article_id' => 'required|exists:ap_class_article,id',
      'cost_price' => 'nullable|numeric|min:0',
      'sale_price' => 'required|numeric|min:0',
      'tax_rate' => 'nullable|numeric|min:0|max:100',
      'is_taxable' => 'nullable|boolean',
      'sunat_code' => 'nullable|string|max:20',
      'warranty_months' => 'nullable|integer|min:0',

      // NEW: Warehouse stock configuration
      'warehouses' => 'nullable|array',
      'warehouses.*.warehouse_id' => 'required|exists:warehouse,id',
      'warehouses.*.initial_quantity' => 'nullable|numeric|min:0',
      'warehouses.*.minimum_stock' => 'nullable|numeric|min:0',
      'warehouses.*.maximum_stock' => 'nullable|numeric|min:0',
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código es obligatorio.',
      'code.unique' => 'El código ya existe.',
      'dyn_code.unique' => 'El código Dynamics ya existe.',
      'name.required' => 'El nombre es obligatorio.',
      'product_category_id.required' => 'La categoría es obligatoria.',
      'product_category_id.exists' => 'La categoría seleccionada no es válida.',
      'brand_id.exists' => 'La marca seleccionada no es válida.',
      'unit_measurement_id.required' => 'La unidad de medida es obligatoria.',
      'unit_measurement_id.exists' => 'La unidad de medida seleccionada no es válida.',
      'sale_price.required' => 'El precio de venta es obligatorio.',
      'sale_price.min' => 'El precio de venta debe ser mayor o igual a 0.',
      'tax_rate.max' => 'La tasa de impuesto no puede ser mayor a 100%.',

      // NEW: Warehouse stock messages
      'warehouses.array' => 'Los almacenes deben ser un array.',
      'warehouses.*.warehouse_id.required' => 'El almacén es obligatorio.',
      'warehouses.*.warehouse_id.exists' => 'El almacén seleccionado no es válido.',
      'warehouses.*.initial_quantity.numeric' => 'La cantidad inicial debe ser numérica.',
      'warehouses.*.initial_quantity.min' => 'La cantidad inicial no puede ser negativa.',
      'warehouses.*.minimum_stock.numeric' => 'El stock mínimo debe ser numérico.',
      'warehouses.*.minimum_stock.min' => 'El stock mínimo no puede ser negativo.',
    ];
  }
}
