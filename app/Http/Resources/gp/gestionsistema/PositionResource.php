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
      'descripcion' => $this->descripcion,
      'area' => $this->area?->name .
        ($this->area?->sede ? ' - ' . $this->area?->sede->suc_abrev : ''),
      'jefatura' => $this->lidership ? $this->lidership->name : 'No Aplica',
      'ntrabajadores' => $this->ntrabajadores,
      'banda_salarial_min' => $this->banda_salarial_min,
      'banda_salarial_media' => $this->banda_salarial_media,
      'banda_salarial_max' => $this->banda_salarial_max,
      'plazo_proceso_seleccion' => $this->plazo_proceso_seleccion,
      'mof_adjunto' => asset($this->mof_adjunto),
      'presupuesto' => $this->presupuesto,
      'tipo_onboarding_id' => $this->tipo_onboarding_id,
      'area_id' => $this->area_id,
      'cargo_id' => $this->cargo_id ?? "",
      'position_head_name' => $this->lidership?->name ?? "",
      'hierarchical_category_id' => $this->hierarchicalCategory?->id,
      'hierarchical_category_name' => $this->hierarchicalCategory?->name ?? "-",
    ];
  }
}
