<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
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
      'reception_date' => $this->reception_date,
      'freight_cost' => $this->freight_cost,
      'shipping_guide_number' => $this->shipping_guide_number,
      'reception_type' => $this->reception_type,
      'notes' => $this->notes ?? "",
      'received_by' => $this->received_by,
      'received_by_user_name' => $this->receivedByUser ? $this->receivedByUser->name : null,
      'total_items' => $this->total_items,
      'total_quantity' => $this->total_quantity,
      'purchase_order_id' => $this->purchase_order_id,
      'warehouse_id' => $this->warehouse_id,
      'status' => $this->status,

      // Relationships
      'purchase_order' => new PurchaseOrderResource($this->purchaseOrder),
      'warehouse' => new WarehouseResource($this->warehouse),
      'carrier' => BusinessPartnersResource::make($this->carrier),
      'details' => PurchaseReceptionDetailResource::collection($this->details->load('product')),
    ];
  }
}
