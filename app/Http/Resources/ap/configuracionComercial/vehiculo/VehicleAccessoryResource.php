<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleAccessoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'accessory_id' => $this->accessory_id,
      'accessory_code' => $this->accessory->code,
      'accessory_description' => $this->accessory->description,
      'unit_price' => $this->unit_price,
      'quantity' => $this->quantity,
      'total' => $this->total,
    ];
  }
}
