<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderPlanningSessionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_order_planning_id' => $this->work_order_planning_id,
      'start_datetime' => $this->start_datetime?->format('Y-m-d H:i:s'),
      'end_datetime' => $this->end_datetime?->format('Y-m-d H:i:s'),
      'hours_worked' => $this->hours_worked,
      'status' => $this->status,
      'pause_reason' => $this->pause_reason,
      'notes' => $this->notes,
      'is_active' => $this->isActive(),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}