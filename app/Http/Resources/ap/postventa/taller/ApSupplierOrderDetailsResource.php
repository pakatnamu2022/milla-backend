<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\maestroGeneral\UnitMeasurementResource;
use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApSupplierOrderDetailsResource extends JsonResource
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
      'ap_supplier_order_id' => $this->ap_supplier_order_id,
      'product_id' => $this->product_id,
      'unit_measurement_id' => $this->unit_measurement_id,
      'note' => $this->note,
      'unit_price' => $this->unit_price,
      'quantity' => round($this->quantity, 2),
      'total' => round($this->total, 2),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      // Relationships
      'product' => new ProductsResource($this->whenLoaded('product')),
      'unit_measurement' => new UnitMeasurementResource($this->whenLoaded('unitMeasurement')),
    ];
  }
}
