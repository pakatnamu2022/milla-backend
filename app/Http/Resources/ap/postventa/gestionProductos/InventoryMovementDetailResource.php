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
      'product' => $this->product,
      'quantity' => $this->quantity,
      'unit_cost' => $this->unit_cost,
      'total_cost' => $this->total_cost,
    ];
  }
}
