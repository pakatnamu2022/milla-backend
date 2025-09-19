<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use App\Http\Resources\gp\maestroGeneral\SedeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApShopResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'description' => $this->description,
      'sedes' => SedeResource::collection($this->whenLoaded('sedes')),
      'status' => $this->status,
    ];
  }
}
