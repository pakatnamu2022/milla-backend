<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'movement_number' => $this->movement_number,
      'movement_type' => $this->movement_type,
      'movement_date' => $this->movement_date->format('Y-m-d'),
      'warehouse_id' => $this->warehouse_id,
      'warehouse_code' => $this->warehouse ? $this->warehouse->dyn_code : null,
      'warehouse' => $this->warehouse,
      'warehouse_destination_id' => $this->warehouse_destination_id,
      'warehouse_destination_code' => $this->warehouseDestination ? $this->warehouseDestination->dyn_code : null,
      'reference_type' => $this->reference_type,
      'reference_id' => $this->reference_id,
      'user_id' => $this->user_id,
      'user_name' => $this->user ? $this->user->name : null,
      'reason_in_out_id' => $this->reason_in_out_id,
      'reason_in_out' => $this->reasonInOut,
      'status' => $this->status,
      'notes' => $this->notes,
      'total_items' => $this->total_items,
      'total_quantity' => $this->total_quantity,
      'details' => InventoryMovementDetailResource::collection($this->whenLoaded('details')),
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }
}
