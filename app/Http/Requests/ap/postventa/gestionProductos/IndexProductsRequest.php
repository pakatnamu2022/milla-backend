<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\IndexRequest;

class IndexProductsRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'search' => 'nullable|string|max:255',
      'product_category_id' => 'nullable|exists:product_category,id',
      'brand_id' => 'nullable|exists:ap_vehicle_brand,id',
      'unit_measurement_id' => 'nullable|exists:unit_measurement,id',
      'warehouse_id' => 'nullable|exists:warehouse,id',
      'product_type' => 'nullable|in:GOOD,SERVICE,KIT',
      'status' => 'nullable|in:ACTIVE,INACTIVE,DISCONTINUED',
      'is_taxable' => 'nullable|boolean',
      'is_fragile' => 'nullable|boolean',
      'is_perishable' => 'nullable|boolean',
      'is_featured' => 'nullable|boolean',
      'is_best_seller' => 'nullable|boolean',
      'cost_price' => 'nullable|numeric|min:0',
      'sale_price' => 'nullable|numeric|min:0',
      'current_stock' => 'nullable|numeric|min:0',
      'low_stock' => 'nullable|boolean',
      'out_of_stock' => 'nullable|boolean',
      'sort_by' => 'nullable|in:code,dyn_code,name,part_number,current_stock,cost_price,sale_price,created_at',
      'sort_order' => 'nullable|in:asc,desc',
    ];
  }
}
