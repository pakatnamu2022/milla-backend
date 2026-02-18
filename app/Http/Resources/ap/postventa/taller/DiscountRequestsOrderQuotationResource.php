<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountRequestsOrderQuotationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'type' => $this->type,
      'ap_order_quotation_id' => $this->ap_order_quotation_id,
      'ap_order_quotation_detail_id' => $this->ap_order_quotation_detail_id,
      'manager_id' => $this->manager_id,
      'approved_id' => $this->approved_id,
      'request_date' => $this->request_date?->format('Y-m-d H:i:s'),
      'requested_discount_percentage' => $this->requested_discount_percentage,
      'requested_discount_amount' => $this->requested_discount_amount,
      'approval_date' => $this->approval_date?->format('Y-m-d H:i:s'),
      'is_approved' => !is_null($this->approved_id),
      'item_type' => $this->item_type,
    ];
  }
}
