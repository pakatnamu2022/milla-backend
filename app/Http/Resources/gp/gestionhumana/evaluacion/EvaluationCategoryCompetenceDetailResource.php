<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCategoryCompetenceDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'active' => (bool)$this->active,
      'competence_id' => $this->competence_id,
      'category_id' => $this->category_id,
      'competence' => EvaluationCompetenceResource::make($this->competence),
      'category' => $this->category?->name,
      'worker' => $this->worker?->nombre_completo,
    ];
  }
}
