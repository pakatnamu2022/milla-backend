<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApDeliveryChecklistResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'vehicle_delivery_id' => $this->vehicle_delivery_id,
      'observations' => $this->observations,
      'status' => $this->status,
      'confirmed_at' => $this->confirmed_at,
      'confirmed_by' => $this->confirmed_by,
      'confirmed_by_name' => $this->confirmedBy?->name,
      'created_by' => $this->created_by,
      'created_at' => $this->created_at,
      'items' => ApDeliveryChecklistItemResource::collection($this->items),
    ];
  }
}

