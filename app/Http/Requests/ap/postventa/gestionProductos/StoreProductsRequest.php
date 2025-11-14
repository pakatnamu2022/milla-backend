<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class StoreProductsRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:products,code',
            'dyn_code' => 'nullable|string|max:50|unique:products,dyn_code',
            'nubefac_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_category_id' => 'required|exists:product_category,id',
            'brand_id' => 'nullable|exists:ap_vehicle_brand,id',
            'unit_measurement_id' => 'required|exists:unit_measurement,id',
            'warehouse_id' => 'nullable|exists:warehouse,id',
            'part_number' => 'nullable|string|max:100',
            'alternative_part_numbers' => 'nullable|array',
            'alternative_part_numbers.*' => 'string|max:100',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'product_type' => 'required|in:GOOD,SERVICE,KIT',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0|gte:minimum_stock',
            'current_stock' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_taxable' => 'nullable|boolean',
            'sunat_code' => 'nullable|string|max:20',
            'warehouse_location' => 'nullable|string|max:100',
            'warranty_months' => 'nullable|integer|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'technical_specifications' => 'nullable|array',
            'compatible_vehicle_models' => 'nullable|array',
            'compatible_vehicle_models.*' => 'integer|exists:ap_vehicle_model,id',
            'is_fragile' => 'nullable|boolean',
            'is_perishable' => 'nullable|boolean',
            'expiration_date' => 'nullable|date|after:today',
            'manufacturer' => 'nullable|string|max:200',
            'country_of_origin' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE,DISCONTINUED',
            'is_featured' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
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
            'warehouse_id.exists' => 'El almacén seleccionado no es válido.',
            'barcode.unique' => 'El código de barras ya existe.',
            'sku.unique' => 'El SKU ya existe.',
            'product_type.required' => 'El tipo de producto es obligatorio.',
            'product_type.in' => 'El tipo de producto debe ser GOOD, SERVICE o KIT.',
            'maximum_stock.gte' => 'El stock máximo debe ser mayor o igual al stock mínimo.',
            'sale_price.required' => 'El precio de venta es obligatorio.',
            'sale_price.min' => 'El precio de venta debe ser mayor o igual a 0.',
            'tax_rate.max' => 'La tasa de impuesto no puede ser mayor a 100%.',
            'expiration_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser ACTIVE, INACTIVE o DISCONTINUED.',
        ];
    }
}
