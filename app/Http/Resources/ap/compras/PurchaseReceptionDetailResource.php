<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceptionDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'purchase_reception_id' => $this->purchase_reception_id,
      'purchase_order_item_id' => $this->purchase_order_item_id,
      'product_id' => $this->product_id,
      'quantity_received' => $this->quantity_received,
      'observed_quantity' => $this->observed_quantity,
      'reception_type' => $this->reception_type,
      'reason_observation' => $this->reason_observation,
      'observation_notes' => $this->observation_notes,
      'bonus_reason' => $this->bonus_reason,
      'batch_number' => $this->batch_number,
      'expiration_date' => $this->expiration_date,
      'notes' => $this->notes,

      // Relationships
      'product' => new ProductsResource($this->whenLoaded('product')),
      'purchase_order_item' => new PurchaseOrderItemResource($this->whenLoaded('purchaseOrderItem')),
    ];
  }
}
