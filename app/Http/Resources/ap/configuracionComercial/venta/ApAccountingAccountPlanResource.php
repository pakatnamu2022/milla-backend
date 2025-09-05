<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAccountingAccountPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return parent::toArray($request);
  }
}
