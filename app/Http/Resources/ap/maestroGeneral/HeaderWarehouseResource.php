<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeaderWarehouseResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'dyn_code' => $this->dyn_code,
      'description' => $this->description,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'type_operation_id' => $this->type_operation_id,
      'type_operation' => $this->typeOperation->description,
      'status' => $this->status,
      'is_received' => $this->is_received,
    ];
  }
}
