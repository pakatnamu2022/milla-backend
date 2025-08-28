<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApClassArticleResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo_dyn' => $this->codigo_dyn,
      'descripcion' => $this->descripcion,
      'cuenta' => $this->cuenta,
      'tipo' => $this->tipo,
      'status' => $this->status
    ];
  }
}
