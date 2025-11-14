<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reception_number' => $this->reception_number,
            'purchase_order_id' => $this->purchase_order_id,
            'reception_date' => $this->reception_date,
            'warehouse_id' => $this->warehouse_id,
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'supplier_invoice_date' => $this->supplier_invoice_date,
            'shipping_guide_number' => $this->shipping_guide_number,
            'status' => $this->status,
            'reception_type' => $this->reception_type,
            'notes' => $this->notes,
            'received_by' => $this->received_by,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'total_items' => $this->total_items,
            'total_quantity' => $this->total_quantity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Computed attributes
            'is_pending_review' => $this->is_pending_review,
            'is_approved' => $this->is_approved,
            'is_rejected' => $this->is_rejected,
            'is_partial' => $this->is_partial,
            'has_bonus_items' => $this->has_bonus_items,
            'has_gift_items' => $this->has_gift_items,
            'has_rejected_items' => $this->has_rejected_items,

            // Relationships
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'received_by_user' => new UserResource($this->whenLoaded('receivedByUser')),
            'reviewed_by_user' => new UserResource($this->whenLoaded('reviewedByUser')),
            'details' => PurchaseReceptionDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}