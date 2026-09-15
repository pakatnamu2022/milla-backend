<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelativeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'persona_id'       => $this->persona_id,
      'nombre_completo'  => $this->nombre_completo,
      'dni'              => $this->dni,
      'fecha_nacimiento' => $this->fecha_nacimiento,
      'sexo'             => $this->sexo,
      'parentesco'       => $this->parentesco,
      'dni_adjunto'      => $this->dni_adjunto,
      'created_at'       => $this->created_at,
    ];
  }
}
