<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationDetailsResource extends JsonResource
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
      'order_quotation_id' => $this->order_quotation_id,
      'item_type' => $this->item_type,
      'product_id' => $this->product_id,
      'product_name' => $this->product ? $this->product->name : '',
      'description' => $this->description,
      'purchase_price' => $this->purchase_price ?? 0,
      'quantity' => $this->quantity ?? 0,
      'unit_measure' => $this->unit_measure ?? '',
      'unit_price' => $this->unit_price ?? 0,
      'discount_percentage' => $this->discount_percentage ?? 0,
      'total_amount' => $this->total_amount ?? 0,
      'observations' => $this->observations,
      'retail_price_external' => $this->retail_price_external,
      'exchange_rate' => $this->exchange_rate,
      'freight_commission' => $this->freight_commission,
      'created_by' => $this->created_by,
      'created_by_name' => $this->createdBy ? $this->createdBy->name : null,

      // Relationships
      'order_quotation' => new ApOrderQuotationsResource($this->whenLoaded('orderQuotation')),
      'product' => new ProductsResource($this->whenLoaded('product')),
      'status' => $this->status,
    ];
  }
}
