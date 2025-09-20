<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApSafeCreditGoalResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'year' => $this->year,
      'month' => $this->month,
      'period' => $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT),
      'goal_amount' => $this->goal_amount,
      'type' => $this->type,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
    ];
  }
}
