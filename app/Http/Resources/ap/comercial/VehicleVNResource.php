<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleVNResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'vin' => $this->vin,
      'order_number' => $this->order_number,
      'year' => $this->year,
      'engine_number' => $this->engine_number,
      'status' => $this->status,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'vehicle_color_id' => $this->vehicle_color_id,
      'supplier_order_type_id' => $this->supplier_order_type_id,
      'engine_type_id' => $this->engine_type_id,
      'sede_id' => $this->sede_id,
      'models_vn' => $this->modelVN->code ?? null,
      'vehicle_color' => $this->vehicleColor->description ?? null,
      'supplier_order_type' => $this->supplierOrderType->description ?? null,
      'engine_type' => $this->engineType->description ?? null,
      'sede' => $this->sede->abreviatura ?? null,
    ];
  }
}
