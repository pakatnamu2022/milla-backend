<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductWarehouseStockResource extends JsonResource
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
      'product_id' => $this->product_id,
      'warehouse_id' => $this->warehouse_id,
      'quantity' => (float)$this->quantity,
      'quantity_in_transit' => (float)$this->quantity_in_transit,
      'quantity_pending_credit_note' => (float)$this->quantity_pending_credit_note,
      'reserved_quantity' => (float)$this->reserved_quantity,
      'available_quantity' => (float)$this->available_quantity,
      'minimum_stock' => (float)$this->minimum_stock,
      'maximum_stock' => (float)$this->maximum_stock,
      'last_movement_date' => $this->last_movement_date?->format('Y-m-d H:i:s'),

      // Computed attributes
      'is_low_stock' => $this->is_low_stock,
      'is_out_of_stock' => $this->is_out_of_stock,
      'stock_status' => $this->stock_status,
      'total_expected_stock' => $this->total_expected_stock,

      // Relationships
      'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),

      // Timestamps
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
