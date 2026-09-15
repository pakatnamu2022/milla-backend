<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkExperienceResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'             => $this->id,
      'persona_id'     => $this->persona_id,
      'institucion'    => $this->institucion,
      'cargo_ocupado'  => $this->cargo_ocupado,
      'motivo_cese'    => $this->motivo_cese,
      'telefono'       => $this->telefono,
      'periodo'        => $this->periodo,
      'jefe_directo'   => $this->jefe_directo,
      'cargo_jefe'     => $this->cargo_jefe,
      'funciones'      => $this->funciones,
      'created_at'     => $this->created_at,
    ];
  }
}
