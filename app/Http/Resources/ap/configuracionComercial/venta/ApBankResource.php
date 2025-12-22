<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApBankResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'account_number' => $this->account_number,
      'cci' => $this->cci,
      'bank_id' => $this->bank_id,
      'bank' => $this->bank->description,
      'description' => $this->description,
      'currency_id' => $this->currency_id,
      'currency' => $this->currency->name,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'status' => $this->status,
    ];
  }
}
