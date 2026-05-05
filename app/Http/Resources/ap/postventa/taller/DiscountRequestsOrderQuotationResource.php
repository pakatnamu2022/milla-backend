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
      'reviewed_by_id' => $this->reviewed_by_id,
      'request_date' => $this->request_date?->format('Y-m-d H:i:s'),
      'requested_discount_percentage' => $this->requested_discount_percentage,
      'requested_discount_amount' => $this->requested_discount_amount,
      'review_date' => $this->review_date?->format('Y-m-d H:i:s'),
      'status' => $this->status,
      'item_type' => $this->item_type,

      // Mantener compatibilidad temporal con frontend
      'approved_id' => $this->reviewed_by_id,
      'approval_date' => $this->review_date?->format('Y-m-d H:i:s'),
    ];
  }
}
