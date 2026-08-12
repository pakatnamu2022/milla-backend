<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConceptObjectivePeriodPvResource extends JsonResource
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
      'objective_sede_period_pv_id' => $this->objective_sede_period_pv_id,
      'objective_sede_period' => $this->objectiveSedePeriod,
      'area_id' => $this->area_id,
      'area' => $this->area,
      'description' => $this->description,
      'is_vehicular_crossing' => $this->is_vehicular_crossing,
      'status' => $this->status,
      'sub_amount' => $this->sub_amount,
      'order' => $this->order,
      'type_planning_ids' => $this->typePlannings->pluck('id'),
      'advisors' => ObjectiveAdvisorsPeriodPvResource::collection($this->advisors),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
