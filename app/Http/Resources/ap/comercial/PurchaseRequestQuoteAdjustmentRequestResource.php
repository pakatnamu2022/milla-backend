<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteAdjustmentRequestResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'purchase_request_quote_id' => $this->purchase_request_quote_id,
      'quote_correlative' => $this->purchaseRequestQuote->correlative ?? null,
      'holder_name' => $this->purchaseRequestQuote->holder->full_name ?? null,
      'requested_by_id' => $this->requested_by_id,
      'requested_by_name' => $this->requestedBy->name ?? null,
      'status' => $this->status,
      'reason' => $this->reason,
      'margin_amount_before' => (float)$this->margin_amount_before,
      'margin_pct_before' => (float)$this->margin_pct_before,
      'margin_amount_after' => (float)$this->margin_amount_after,
      'margin_pct_after' => (float)$this->margin_pct_after,
      'resolved_by_id' => $this->resolved_by_id,
      'resolved_by_name' => $this->resolvedBy->name ?? null,
      'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
      'rejection_reason' => $this->rejection_reason,
      'items' => PurchaseRequestQuoteAdjustmentItemResource::collection($this->whenLoaded('items')),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}
