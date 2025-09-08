<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAccountingAccountPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'account' => $this->account,
      'description' => $this->description,
      'accounting_type_id' => $this->accounting_type_id,
      'accounting_type' => $this->typeAccount->description,
      'status' => $this->status,
    ];
  }
}
