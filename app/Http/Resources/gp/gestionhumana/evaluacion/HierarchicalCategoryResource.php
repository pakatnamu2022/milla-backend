<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HierarchicalCategoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    // Combinar issues con validación de competencias
    $issues = is_string($this->issues) ? json_decode($this->issues, true) : $this->issues;
    $issues = $issues ?? [];

    // Si hasObjectives es false y no tiene competencias, agregar el issue
    if (
      ($this->hasObjectives === false || $this->hasObjectives === 0) &&
      isset($this->competences_count) &&
      $this->competences_count == 0
    ) {
      $issues[] = 'La categoría debe tener al menos una competencia asignada cuando no tiene objetivos.';
    }

    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'pass' => $this->pass,
      'issues' => $issues,
      'objectives' => EvaluationObjectiveResource::collection($this->objectives),
      'competences' => EvaluationCompetenceResource::collection($this->competences),
      'excluded_from_evaluation' => (bool)$this->excluded_from_evaluation,
      'hasObjectives' => (bool)$this->hasObjectives,
      'children' => HierarchicalCategoryDetailResource::collection($this->children),
    ];
  }
}
