<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApFamiliesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'description' => $this->description,
      'brand_id' => $this->brand_id,
      'brand' => $this->brand->name,
      'status' => $this->status,
      'models_count' => $this->models()->count(),
    ];
  }
}
