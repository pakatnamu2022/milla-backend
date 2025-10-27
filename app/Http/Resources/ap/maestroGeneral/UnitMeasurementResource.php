<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitMeasurementResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'dyn_code' => $this->dyn_code,
      'nubefac_code' => $this->nubefac_code,
      'description' => $this->description,
      'status' => (bool)$this->status,
    ];
  }
}
