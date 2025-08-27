<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCategoryObjectiveDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'goal' => round($this->goal, 2),
      'weight' => round($this->weight, 2),
      'objective_id' => $this->objective_id,
      'category_id' => $this->category_id,
      'objective' => $this->objective?->name,
      'category' => $this->category?->name,
    ];
  }
}
