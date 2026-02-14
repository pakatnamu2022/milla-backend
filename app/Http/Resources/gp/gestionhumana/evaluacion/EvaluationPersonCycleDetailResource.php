<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonCycleDetailResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'person' => $this->person,
      'chief' => $this->chief,
      'position' => $this->position,
      'sede' => $this->sede,
      'area' => $this->area,
      'category' => $this->category,
      'objective' => $this->objective,
      'objective_description' => $this->objectiveModel?->description,
      'isAscending' => $this->isAscending,
      'goal' => round($this->goal, 2),
      'weight' => round($this->weight, 2),
      'status' => $this->status,
      'metric' => $this->metric,
      'end_date_objectives' => $this->end_date_objectives,
    ];
  }
}
