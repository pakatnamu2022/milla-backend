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
      'codigo' => $this->codigo,
      'descripcion' => $this->descripcion,
      'marca_id' => $this->marca_id,
      'marca' => $this->marca->nombre,
      'status' => $this->status,
    ];
  }
}
