<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'group_number' => $this->group_number,
      'work_order_id' => $this->work_order_id,
      'type_planning_id' => $this->type_planning_id,
      'type_planning_name' => $this->typePlanning ? $this->typePlanning->description : null,
      'type_operation_id' => $this->type_operation_id,
      'type_operation_name' => $this->typeOperation ? $this->typeOperation->description : null,
      'description' => $this->description,
    ];
  }
}
