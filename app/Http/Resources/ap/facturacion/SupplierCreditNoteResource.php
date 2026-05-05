<?php

namespace App\Http\Resources\ap\facturacion;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\compras\PurchaseOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierCreditNoteResource extends JsonResource
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
      'credit_note_number' => $this->credit_note_number,
      'purchase_order_id' => $this->purchase_order_id,
      'purchase_reception_id' => $this->purchase_reception_id,
      'supplier_id' => $this->supplier_id,
      'credit_note_date' => $this->credit_note_date,
      'reason' => $this->reason,
      'subtotal' => $this->subtotal,
      'tax_amount' => $this->tax_amount,
      'total' => $this->total,
      'status' => $this->status,
      'notes' => $this->notes,
      'approved_by' => $this->approved_by,
      'approved_at' => $this->approved_at,
      'purchase_order' => new PurchaseOrderResource($this->purchaseOrder),
      'details' => SupplierCreditNoteDetailResource::collection($this->details->load('product')),
    ];
  }
}
