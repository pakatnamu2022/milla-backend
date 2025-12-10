<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApWorkOrderAssignOperatorResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_order_id' => $this->work_order_id,
      'work_order_correlative' => $this->workOrder ? $this->workOrder->correlative : null,
      'group_number' => $this->group_number,
      'work_order_item_description' => $this->workOrderItem ? $this->workOrderItem->description : null,
      'operator_id' => $this->operator_id,
      'operator_name' => $this->operator ? $this->operator->nombre_completo : null,
      'registered_by' => $this->registered_by,
      'registered_by_name' => $this->registeredBy ? $this->registeredBy->name : null,
      'status' => $this->status,
      'observations' => $this->observations,
    ];
  }
}
