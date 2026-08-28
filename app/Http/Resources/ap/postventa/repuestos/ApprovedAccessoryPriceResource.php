<?php

namespace App\Http\Resources\ap\postventa\repuestos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovedAccessoryPriceResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'body_type_id' => $this->body_type_id,
      'body_type' => $this->bodyType->description ?? null,
      'body_type_code' => $this->bodyType->code ?? null,
      'price' => (float) $this->price,
    ];
  }
}
