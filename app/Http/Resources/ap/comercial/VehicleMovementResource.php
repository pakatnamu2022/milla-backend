<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleMovementResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'                           => $this->id,
      'date'                         => $this->movement_date,
      'observation'                  => $this->observation,
      'status'                       => $this->status,
      'status_color'                 => $this->status_color,
      'ap_vehicle_status_id'         => $this->ap_vehicle_status_id,
      'ap_vehicle_purchase_order_id' => $this->ap_vehicle_purchase_order_id,
      'previous_status'              => $this->when($this->previous_status_id, [
        'id'          => $this->previousStatus?->id,
        'description' => $this->previousStatus?->description,
        'color'       => $this->previousStatus?->color,
      ]),
      'new_status'                   => $this->when($this->new_status_id, [
        'id'          => $this->newStatus?->id,
        'description' => $this->newStatus?->description,
        'color'       => $this->newStatus?->color,
      ]),
      'warehouse'                    => $this->when($this->warehouse_id, [
        'id'          => $this->warehouse?->id,
        'description' => $this->warehouse?->description,
        'dyn_code'    => $this->warehouse?->dyn_code,
      ]),
      'origin_warehouse'             => $this->when($this->origin_warehouse_id, [
        'id'          => $this->originWarehouse?->id,
        'description' => $this->originWarehouse?->description,
        'dyn_code'    => $this->originWarehouse?->dyn_code,
      ]),
    ];
  }
}
