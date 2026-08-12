<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjectiveSedePeriodPvResource extends JsonResource
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
      'sede_id' => $this->sede_id,
      'sede' => $this->sede,
      'year' => $this->year,
      'month' => $this->month,
      'amount' => $this->amount,
      'concept_objectives' => ConceptObjectivePeriodPvResource::collection(
        $this->whenLoaded('conceptObjectives') ? $this->conceptObjectives->sortBy('order')->values() : []
      ),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
