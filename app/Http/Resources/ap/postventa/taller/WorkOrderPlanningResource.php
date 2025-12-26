<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderPlanningResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_order_correlative' => $this->workOrder ? $this->workOrder->correlative : null,
      'work_order_id' => $this->work_order_id,
      'worker_id' => $this->worker_id,
      'worker_name' => $this->worker ? $this->worker->nombre_completo : null,
      'description' => $this->description,
      'estimated_hours' => $this->estimated_hours,
      'actual_hours' => $this->actual_hours ?? 0,
      'planned_start_datetime' => $this->planned_start_datetime?->format('Y-m-d H:i:s'),
      'planned_end_datetime' => $this->planned_end_datetime?->format('Y-m-d H:i:s'),
      'actual_start_datetime' => $this->actual_start_datetime?->format('Y-m-d H:i:s'),
      'actual_end_datetime' => $this->actual_end_datetime?->format('Y-m-d H:i:s'),
      'type' => $this->type,
      'status' => $this->status,
      'has_active_session' => (bool)$this->activeSession(),
      'sessions_count' => $this->sessions ? $this->sessions->count() : 0,
      'sessions' => WorkOrderPlanningSessionResource::collection($this->whenLoaded('sessions')),
    ];
  }
}
