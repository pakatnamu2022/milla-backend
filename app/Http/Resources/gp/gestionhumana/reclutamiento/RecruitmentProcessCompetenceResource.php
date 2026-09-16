<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentProcessCompetenceResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                 => $this->id,
      'sub_competencia_id' => $this->sub_competencia_id,
      'nombre'             => $this->whenLoaded('subCompetence', fn() => $this->subCompetence?->nombre),
      'competencia'        => $this->whenLoaded('subCompetence', fn() => $this->subCompetence?->competence?->nombre),
      'orden'              => $this->orden,
    ];
  }
}
