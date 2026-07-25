<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteAccessoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                    => $this->id,
      'approved_accessory_id' => $this->approved_accessory_id,
      'description'           => $this->approvedAccessory->description ?? null,
      'type'                  => $this->type,
      'quantity'              => $this->quantity,
      'price'                 => $this->price,
      'additional_price'      => $this->additional_price,
      'total'                 => $this->total,
      'type_currency_id'      => $this->type_currency_id,
      'type_currency_code'    => $this->typeCurrency->code ?? null,
      'type_currency_symbol'  => $this->typeCurrency->symbol ?? null,
    ];
  }
}
