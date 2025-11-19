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
      'minimum_stock' => $this->minimum_stock,
      'maximum_stock' => $this->maximum_stock,
      'current_stock' => $this->current_stock,
      'cost_price' => $this->cost_price,
      'sale_price' => $this->sale_price,
      'tax_rate' => $this->tax_rate,
      'is_taxable' => $this->is_taxable,
      'sunat_code' => $this->sunat_code,
      'warranty_months' => $this->warranty_months,
      'status' => $this->status,
      'brand_name' => $this->brand->name,
      'category_name' => $this->category->description,
      'unit_measurement_name' => $this->unitMeasurement->description,

      // Computed attributes
//      'price_with_tax' => $this->price_with_tax,
//      'cost_with_tax' => $this->cost_with_tax,
//      'wholesale_price_with_tax' => $this->wholesale_price_with_tax,
//      'is_low_stock' => $this->is_low_stock,
//      'needs_reorder' => $this->needs_reorder,

      // Relationships
      'category' => new ProductCategoryResource($this->whenLoaded('category')),
      'brand' => new ApVehicleBrandResource($this->whenLoaded('brand')),
      'unit_measurement' => new UnitMeasurementResource($this->whenLoaded('unitMeasurement')),
      'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
    ];
  }
}
