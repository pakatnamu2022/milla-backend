<?php

namespace App\Http\Resources\ap\postventa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovedAccessoriesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'type' => $this->type,
      'description' => $this->description,
      'exchange_rate' => $this->exchange_rate,
      'price' => $this->price,
      'status' => $this->status,
      'type_currency_id' => $this->type_currency_id,
      'body_type_id' => $this->body_type_id,
      'type_currency' => $this->typeCurrency,
      'body_type' => $this->bodyType,
    ];
  }
}
