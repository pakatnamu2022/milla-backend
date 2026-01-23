<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderPurchaseRequestDetailsResource extends JsonResource
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
      'order_purchase_request_id' => $this->order_purchase_request_id,
      'product_id' => $this->product_id,
      'quantity' => $this->quantity,
      'notes' => $this->notes,
      'requested_delivery_date' => $this->requested_delivery_date,
      'status' => $this->status,
      'has_supplier_order' => $this->supplierOrders()->exists(),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      // Relationships
      'product' => new ProductsResource($this->whenLoaded('product')),
    ];
  }
}
