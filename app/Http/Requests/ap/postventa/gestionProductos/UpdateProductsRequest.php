<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateProductsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'required',
        'string',
        'max:50',
        Rule::unique('products', 'code')->ignore($this->route('product'))->whereNull('deleted_at'),
      ],
      'dyn_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('products', 'dyn_code')->ignore($this->route('product'))->whereNull('deleted_at'),
      ],
      'nubefac_code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('products', 'nubefac_code')->ignore($this->route('product'))->whereNull('deleted_at'),
      ],
      'name' => 'sometimes|required|string|max:255',
      'description' => 'nullable|string',
      'product_category_id' => 'sometimes|required|exists:product_category,id',
      'brand_id' => 'nullable|exists:ap_vehicle_brand,id',
      'unit_measurement_id' => 'sometimes|required|exists:unit_measurement,id',
      'ap_class_article_id' => 'sometimes|required|exists:ap_class_article,id',
      'cost_price' => 'nullable|numeric|min:0',

      'sale_price' => 'sometimes|required|numeric|min:0',
      'tax_rate' => 'nullable|numeric|min:0|max:100',
      'is_taxable' => 'nullable|boolean',
      'sunat_code' => 'nullable|string|max:20',
      'warranty_months' => 'nullable|integer|min:0',
      'status' => 'sometimes|required|in:ACTIVE,INACTIVE,DISCONTINUED',
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
      'status.required' => 'El estado es obligatorio.',
      'status.in' => 'El estado debe ser ACTIVE, INACTIVE o DISCONTINUED.',
    ];
  }
}
