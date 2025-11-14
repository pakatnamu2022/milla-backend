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
            'quantity_accepted' => $this->quantity_accepted,
            'quantity_rejected' => $this->quantity_rejected,
            'reception_type' => $this->reception_type,
            'unit_cost' => $this->unit_cost,
            'is_charged' => $this->is_charged,
            'total_cost' => $this->total_cost,
            'rejection_reason' => $this->rejection_reason,
            'rejection_notes' => $this->rejection_notes,
            'bonus_reason' => $this->bonus_reason,
            'batch_number' => $this->batch_number,
            'expiration_date' => $this->expiration_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed attributes
            'is_ordered' => $this->is_ordered,
            'is_bonus' => $this->is_bonus,
            'is_gift' => $this->is_gift,
            'is_sample' => $this->is_sample,
            'has_rejected_quantity' => $this->has_rejected_quantity,
            'is_fully_accepted' => $this->is_fully_accepted,
            'acceptance_rate' => $this->acceptance_rate,

            // Relationships
            'product' => new ProductsResource($this->whenLoaded('product')),
            'purchase_order_item' => new PurchaseOrderItemResource($this->whenLoaded('purchaseOrderItem')),
        ];
    }
}