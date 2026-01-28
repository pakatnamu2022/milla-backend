<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\maestroGeneral\UnitMeasurementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
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
      'description' => $this->description ?? null,
      'unit_price' => (float)$this->unit_price,
      'quantity' => (int)$this->quantity,
      'total' => (float)$this->total,
      'is_vehicle' => (bool)$this->is_vehicle,
      'unit_measurement' => UnitMeasurementResource::make($this->unitMeasurement) ?? null,
      'product_id' => $this->product_id ?? null,
      'product_name' => $this->product->name ?? null,
      'product_code' => $this->product->code ?? null,
    ];
  }
}
