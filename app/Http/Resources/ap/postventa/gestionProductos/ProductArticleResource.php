<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductArticleResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->dyn_code,
      'name' => $this->name,
      'description' => $this->description,
      'brand_id' => $this->brand_id,
      'brand' => $this->brand->name ?? '',
      'brand_dyn' => $this->brand->dyn_code ?? '',
      'category_id' => $this->product_category_id,
      'category' => $this->category->description ?? '',
      'class_id' => $this->ap_class_article_id,
      'class' => $this->articleClass->description ?? '',
      'class_dyn' => $this->articleClass->dyn_code ?? '',
      'unit_measurement_id' => $this->unit_measurement_id,
      'unit_measurement' => $this->unitMeasurement->dyn_code ?? '',
      'unit_measurement_description' => $this->unitMeasurement->description ?? '',
      'warranty_months' => $this->warranty_months,
      'status' => $this->status,
    ];
  }
}
