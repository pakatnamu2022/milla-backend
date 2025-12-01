<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleBrandResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'dyn_code' => $this->dyn_code,
      'name' => $this->name,
      'description' => $this->description,
      'logo' => $this->logo,
      'logo_min' => $this->logo_min,
      'type_operation_id' => $this->type_operation_id,
      'status' => $this->status,
      'group_id' => $this->group_id,
      'group' => $this->group->description,
      'sede_id' => $this->group->sede_id,
    ];
  }
}
