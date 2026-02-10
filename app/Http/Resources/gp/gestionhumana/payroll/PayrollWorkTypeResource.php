<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollWorkTypeResource extends JsonResource
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
      'code' => $this->code,
      'name' => $this->name,
      'description' => $this->description,
      'shift_type' => $this->shift_type,
      'multiplier' => (float) $this->multiplier,
      'base_hours' => (int) $this->base_hours,
      'is_extra_hours' => (bool) $this->is_extra_hours,
      'is_night_shift' => (bool) $this->is_night_shift,
      'is_holiday' => (bool) $this->is_holiday,
      'is_sunday' => (bool) $this->is_sunday,
      'active' => (bool) $this->active,
      'order' => (int) $this->order,
      'segments' => PayrollWorkTypeSegmentResource::collection($this->whenLoaded('segments')),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
