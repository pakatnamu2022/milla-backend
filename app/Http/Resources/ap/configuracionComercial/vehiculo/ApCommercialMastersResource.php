<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApCommercialMastersResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'descripcion' => $this->descripcion,
      'tipo' => $this->tipo,
      'status' => $this->status,
    ];
  }
}
