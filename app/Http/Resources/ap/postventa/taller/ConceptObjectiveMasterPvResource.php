<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConceptObjectiveMasterPvResource extends JsonResource
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
      'area_id' => $this->area_id,
      'area' => $this->area,
      'description' => $this->description,
      'is_vehicular_crossing' => $this->is_vehicular_crossing,
      'status' => $this->status,
      'order' => $this->order,
      'type_planning_ids' => $this->typePlannings->pluck('id'),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
