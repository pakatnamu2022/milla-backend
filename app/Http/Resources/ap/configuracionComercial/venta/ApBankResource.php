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
      'description' => $this->bank->description . ' (' . $this->currency->name . ')',
      'currency_id' => $this->currency_id,
      'currency' => $this->currency->name,
//      'company_branch_id' => $this->company_branch_id,
//      'company_branch' => $this->companyBranch->abbreviation,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'status' => $this->status,
    ];
  }
}
