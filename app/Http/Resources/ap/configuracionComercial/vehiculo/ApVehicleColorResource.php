<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleColorResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'descripcion' => $this->descripcion,
    ];
  }
}
