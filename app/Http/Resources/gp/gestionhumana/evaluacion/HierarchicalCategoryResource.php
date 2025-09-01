<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HierarchicalCategoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'pass' => $this->pass,
      'issues' => $this->issues,
      'objectives' => EvaluationObjectiveResource::collection($this->objectives),
      'competences' => EvaluationCompetenceResource::collection($this->competences),
      'excluded_from_evaluation' => (bool)$this->excluded_from_evaluation,
      'children' => HierarchicalCategoryDetailResource::collection($this->children),
    ];
  }
}
