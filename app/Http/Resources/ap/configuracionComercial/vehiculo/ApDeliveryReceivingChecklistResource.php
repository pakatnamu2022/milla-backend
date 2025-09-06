<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApDeliveryReceivingChecklistResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'description' => $this->description,
      'type' => $this->type,
      'status' => $this->status,
      'category_id' => $this->category_id,
      'category' => $this->category->description,
    ];
  }
}
