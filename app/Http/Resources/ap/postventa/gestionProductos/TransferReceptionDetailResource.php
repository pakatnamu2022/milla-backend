<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferReceptionDetailResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'transfer_reception_id' => $this->transfer_reception_id,
      'product_id' => $this->product_id, // null for SERVICIO type
      'quantity_sent' => $this->quantity_sent,
      'quantity_received' => $this->quantity_received,
      'observed_quantity' => $this->observed_quantity,
      'reason_observation' => $this->reason_observation,
      'observation_notes' => $this->observation_notes,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      // Relationships
      'product' => new ProductsResource($this->whenLoaded('product')),

      // Calculated attributes
      'quantity_accepted' => $this->quantity_accepted,
      'has_observations' => $this->has_observations,
      'observation_percentage' => $this->observation_percentage,
    ];
  }
}
