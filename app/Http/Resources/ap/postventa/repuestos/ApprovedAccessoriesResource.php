<?php

namespace App\Http\Resources\ap\postventa\repuestos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovedAccessoriesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $prices = $this->relationLoaded('prices') ? $this->prices : collect();

    return [
      'id' => $this->id,
      'code' => $this->code,
      'type_operation_id' => $this->type_operation_id,
      'type_operation' => $this->typeOperation->description ?? null,
      'description' => $this->description,
      'status' => $this->status,
      'type_currency_id' => $this->type_currency_id,
      'type_currency' => $this->typeCurrency->code ?? null,
      'currency_symbol' => $this->typeCurrency->symbol ?? null,
      'prices' => ApprovedAccessoryPriceResource::collection($prices),
      // IDs de carrocería a las que aplica este accesorio (útil para filtrar en el front).
      'body_type_ids' => $prices->pluck('body_type_id')->values(),
    ];
  }
}
