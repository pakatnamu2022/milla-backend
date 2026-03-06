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
      'code_dynamics' => $this->code_dynamics,
      'description' => $this->description,
      'is_detraction' => $this->is_detraction,
      'status' => $this->status,
    ];
  }
}
