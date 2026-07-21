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
      'time_spent_decimal' => $this->time_spent_decimal,
      'hourly_rate' => $this->hourly_rate,
      'discount_percentage' => $this->discount_percentage,
      'total_cost' => (float)$this->total_cost,
      'net_amount' => (float)$this->net_amount,
      'tax_amount' => (float)$this->tax_amount,
      'is_deductible' => (bool)$this->is_deductible,
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
