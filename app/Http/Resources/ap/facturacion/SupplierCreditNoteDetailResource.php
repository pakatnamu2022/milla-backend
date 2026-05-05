<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\ap\postventa\gestionProductos\ProductsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierCreditNoteDetailResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'supplier_credit_note_id' => $this->supplier_credit_note_id,
      'product_id' => $this->product_id,
      'quantity' => $this->quantity,
      'unit_price' => $this->unit_price,
      'discount_percentage' => $this->discount_percentage,
      'tax_rate' => $this->tax_rate,
      'subtotal' => $this->subtotal,
      'notes' => $this->notes,
      'product' => new ProductsResource($this->whenLoaded('product')),
    ];
  }
}
