<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleHierarchicalCategoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'excluded_from_evaluation' => (bool)$this->excluded_from_evaluation,
      'hasObjectives' => (bool)$this->hasObjectives,
    ];
  }
}
