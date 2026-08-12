<?php

namespace App\Http\Resources\ap\marketing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktActivityLocationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'            => $this->id,
      'activity_id'   => $this->activity_id,
      'sede_id'       => $this->sede_id,
      'sede'          => $this->whenLoaded('sede', fn() => [
        'id'   => $this->sede->id,
        'name' => $this->sede->name,
      ]),
      'location_name' => $this->location_name,
      'currency_id'   => $this->currency_id,
      'currency'      => $this->whenLoaded('currency', fn() => [
        'id'     => $this->currency->id,
        'name'   => $this->currency->name,
        'code'   => $this->currency->code,
        'symbol' => $this->currency->symbol,
      ]),
      'amount'        => $this->amount,
      'notes'         => $this->notes,
      'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
