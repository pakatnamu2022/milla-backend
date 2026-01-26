<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollWorkTypeSegmentResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'work_type_id' => $this->work_type_id,
      'segment_type' => $this->segment_type,
      'segment_order' => $this->segment_order,
      'duration_hours' => $this->duration_hours ? (float) $this->duration_hours : null,
      'multiplier' => $this->multiplier ? (float) $this->multiplier : null,
      'description' => $this->description,
      'created_at' => $this->created_at?->toISOString(),
      'updated_at' => $this->updated_at?->toISOString(),
    ];
  }
}
