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
      'product_id' => $this->product_id,
      'quantity_sent' => $this->quantity_sent,
      'quantity_received' => $this->quantity_received,
      'observed_quantity' => $this->observed_quantity,
      'reason_observation' => $this->reason_observation,
      'observation_notes' => $this->observation_notes,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      // Relationships
      'product' => $this->whenLoaded('product', function () {
        return [
          'id' => $this->product->id,
          'code' => $this->product->code,
          'name' => $this->product->name,
          'description' => $this->product->description,
          'unit_type' => $this->product->unit_type,
        ];
      }),

      // Calculated attributes
      'quantity_accepted' => $this->quantity_accepted,
      'has_observations' => $this->has_observations,
      'observation_percentage' => $this->observation_percentage,
    ];
  }
}
