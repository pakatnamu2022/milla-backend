<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignSedeResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'sede_id' => $this->id,
      'abreviatura' => $this->abreviatura,
      'asesores' => $this->asesores->map(fn($asesor) => [
        'id' => $asesor->id,
        'nombre_completo' => $asesor->nombre_completo,
      ]),
    ];
  }
}
