<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleBrandResource;
use App\Http\Resources\ap\maestroGeneral\UnitMeasurementResource;
use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'dyn_code' => $this->dyn_code,
      'nubefac_code' => $this->nubefac_code,
      'name' => $this->name,
      'description' => $this->description,
      'product_category_id' => $this->product_category_id,
      'brand_id' => $this->brand_id,
      'unit_measurement_id' => $this->unit_measurement_id,
      'warehouse_id' => $this->warehouse_id,
      'ap_class_article_id' => $this->ap_class_article_id,
      'part_number' => $this->part_number,
      'alternative_part_numbers' => $this->alternative_part_numbers,
      'sku' => $this->sku,
      'minimum_stock' => $this->minimum_stock,
      'maximum_stock' => $this->maximum_stock,
      'current_stock' => $this->current_stock,
      'cost_price' => $this->cost_price,
      'sale_price' => $this->sale_price,
      'wholesale_price' => $this->wholesale_price,
      'tax_rate' => $this->tax_rate,
      'is_taxable' => $this->is_taxable,
      'sunat_code' => $this->sunat_code,
      'warehouse_location' => $this->warehouse_location,
      'warranty_months' => $this->warranty_months,
      'reorder_point' => $this->reorder_point,
      'reorder_quantity' => $this->reorder_quantity,
      'lead_time_days' => $this->lead_time_days,
      'weight' => $this->weight,
      'dimensions' => $this->dimensions,
      'image' => $this->image,
      'images' => $this->images,
      'technical_specifications' => $this->technical_specifications,
      'compatible_vehicle_models' => $this->compatible_vehicle_models,
      'is_fragile' => $this->is_fragile,
      'is_perishable' => $this->is_perishable,
      'expiration_date' => $this->expiration_date,
      'manufacturer' => $this->manufacturer,
      'country_of_origin' => $this->country_of_origin,
      'status' => $this->status,
      'is_featured' => $this->is_featured,
      'is_best_seller' => $this->is_best_seller,
      'brand_name' => $this->brand->name,
      'category_name' => $this->category->description,
      'unit_measurement_name' => $this->unitMeasurement->description,

      // Computed attributes
      'price_with_tax' => $this->price_with_tax,
      'cost_with_tax' => $this->cost_with_tax,
      'wholesale_price_with_tax' => $this->wholesale_price_with_tax,
      'is_low_stock' => $this->is_low_stock,
      'needs_reorder' => $this->needs_reorder,

      // Relationships
      'category' => new ProductCategoryResource($this->whenLoaded('category')),
      'brand' => new ApVehicleBrandResource($this->whenLoaded('brand')),
      'unit_measurement' => new UnitMeasurementResource($this->whenLoaded('unitMeasurement')),
      'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
    ];
  }
}
