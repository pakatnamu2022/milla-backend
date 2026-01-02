<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApWorkOrderPartsResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_order_id' => $this->work_order_id,
      'work_order_correlative' => $this->workOrder ? $this->workOrder->correlative : null,
      'group_number' => $this->group_number,
      'product_id' => $this->product_id,
      'product_name' => $this->product ? $this->product->name : null,
      'product_code' => $this->product ? $this->product->code : null,
      'warehouse_id' => $this->warehouse_id,
      'warehouse_name' => $this->warehouse ? $this->warehouse->description : null,
      'quantity_used' => (float)$this->quantity_used,
      'unit_cost' => (float)$this->unit_cost,
      'unit_price' => (float)$this->unit_price,
      'discount_percentage' => (float)$this->discount_percentage,
      'subtotal' => (float)$this->subtotal,
      'tax_amount' => (float)$this->tax_amount,
      'total_amount' => (float)$this->total_amount,
      'registered_by' => $this->registered_by,
      'is_delivered' => (bool)$this->is_delivered,
      'group_number' => $this->group_number,
      'registered_by_name' => $this->registeredBy ? $this->registeredBy->name : null,
    ];
  }
}
