<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApBrandResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'codigo_dyn' => $this->codigo_dyn,
      'grupo_id' => $this->grupo_id,
      'name' => $this->name,
      'descripcion' => $this->descripcion,
      'logo' => $this->logo,
      'logo_min' => $this->logo_min,
      'grupo' => $this->grupo?->name,
    ];
  }
}
