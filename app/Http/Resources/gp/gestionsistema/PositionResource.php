<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'area' => $this->area?->name .
        ($this->area?->sede ? ' - ' . $this->area?->sede->suc_abrev : ''),
      'descripcion' => $this->descripcion,
      'area_id' => $this->area_id,
      'tipo_onboarding_id' => $this->tipo_onboarding_id,
      'plazo_proceso_seleccion' => $this->plazo_proceso_seleccion,
      'mof_adjunto' => asset($this->mof_adjunto),
      'cargo_id' => $this->cargo_id,
    ];
  }
}
