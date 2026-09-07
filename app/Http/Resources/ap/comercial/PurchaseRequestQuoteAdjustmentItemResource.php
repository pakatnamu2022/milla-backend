<?php

namespace App\Http\Resources\ap\comercial;

use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteAdjustmentItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $isGift = $this->item_type === PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT;

    return [
      'id' => $this->id,
      'action' => $this->action,
      'item_type' => $this->item_type ?? PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_BONUS_DISCOUNT,

      // Bono / descuento
      'discount_coupon_id' => $this->discount_coupon_id,
      'concept_code_id' => $this->concept_code_id,
      'concept_code' => $this->conceptCode->description ?? null,
      'type' => $this->type,
      'is_negative' => $this->is_negative,
      'has_retention' => $this->has_retention,

      // Obsequio
      'accessory_detail_id' => $this->accessory_detail_id,
      'approved_accessory_id' => $this->approved_accessory_id,
      'accessory_label' => $isGift
        ? ($this->accessoryDetail->approvedAccessory->description
          ?? $this->approvedAccessory->description
          ?? null)
        : null,
      'quantity' => $this->quantity,
      'additional_price' => $this->additional_price,

      // Montos (para bono: precio unitario; para obsequio: total)
      'previous_valor_unitario' => $this->previous_valor_unitario,
      'new_valor_unitario' => $this->new_valor_unitario,
      'previous_precio_unitario' => $this->previous_precio_unitario,
      'new_precio_unitario' => $this->new_precio_unitario,
    ];
  }
}
