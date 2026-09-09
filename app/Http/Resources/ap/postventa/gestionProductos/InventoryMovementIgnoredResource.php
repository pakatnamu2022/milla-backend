<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementIgnoredResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'movement_number' => $this->movement_number,
      'movement_number_dyn' => $this->movement_number_dyn,
      'movement_type' => $this->movement_type,
      'movement_date' => $this->movement_date,
      'is_inbound' => $this->is_inbound,
      'is_outbound' => $this->is_outbound,
      'user_name' => $this->user ? $this->user->name : null,
      'notes' => $this->notes,
      'quantity_in' => $this->quantity_in,
      'quantity_out' => $this->quantity_out,
      'discarded_reason' => $this->ignore_reason,
      'discarded_by_name' => $this->ignoredByUser ? $this->ignoredByUser->name : null,
      'discarded_at' => $this->ignored_at ? $this->ignored_at->format('Y-m-d H:i:s') : null,
    ];
  }
}