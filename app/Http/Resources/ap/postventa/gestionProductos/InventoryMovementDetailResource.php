<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'inventory_movement_id' => $this->inventory_movement_id,
      'product_id' => $this->product_id,
      'product' => $this->product, // null for SERVICIO type
      'quantity' => $this->quantity,
      'unit_cost' => $this->unit_cost,
      'total_cost' => $this->total_cost,
      'batch_number' => $this->batch_number,
      'expiration_date' => $this->expiration_date,
      'notes' => $this->notes, // Contains description for SERVICIO type
      'description' => $this->notes, // Alias for frontend (same as notes)
    ];
  }
}
