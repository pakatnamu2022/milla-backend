<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteOtherResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'description' => $this->description,
      'type'        => $this->type,
      'value'       => $this->value,
      'amount'      => round($this->amount, 2),
    ];
  }
}
