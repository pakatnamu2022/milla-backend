<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApWorkOrderPartDeliveryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_order_part_id' => $this->work_order_part_id,
      'delivered_to' => $this->delivered_to,
      'delivered_to_name' => $this->deliveredToUser ? $this->deliveredToUser->name : null,
      'delivered_quantity' => (float)$this->delivered_quantity,
      'delivered_date' => $this->delivered_date,
      'delivered_by' => $this->delivered_by,
      'delivered_by_name' => $this->deliveredByUser ? $this->deliveredByUser->name : null,
      'is_received' => (bool)$this->is_received,
      'received_date' => $this->received_date,
      'received_signature_url' => $this->received_signature_url,
      'received_by' => $this->received_by,
      'received_by_name' => $this->receivedByUser ? $this->receivedByUser->name : null,
    ];
  }
}

