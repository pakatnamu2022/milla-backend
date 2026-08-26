<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteAdjustmentItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'action' => $this->action,
      'discount_coupon_id' => $this->discount_coupon_id,
      'concept_code_id' => $this->concept_code_id,
      'concept_code' => $this->conceptCode->description ?? null,
      'type' => $this->type,
      'is_negative' => $this->is_negative,
      'has_retention' => $this->has_retention,
      'previous_valor_unitario' => $this->previous_valor_unitario,
      'new_valor_unitario' => $this->new_valor_unitario,
      'previous_precio_unitario' => $this->previous_precio_unitario,
      'new_precio_unitario' => $this->new_precio_unitario,
    ];
  }
}
