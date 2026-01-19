<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderLabourResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'group_number' => $this->group_number,
      'description' => $this->description,
      'time_spent' => $this->time_spent,
      'hourly_rate' => $this->hourly_rate,
      'total_cost' => $this->total_cost,
      'worker_id' => $this->worker_id,
      'worker_full_name' => $this->worker ? $this->worker->nombre_completo : null,
      'work_order_id' => $this->work_order_id,
      'work_order' => $this->whenLoaded('workOrder', function () {
        return [
          'id' => $this->workOrder->id,
          'correlative' => $this->workOrder->correlative,
        ];
      }),
    ];
  }
}
