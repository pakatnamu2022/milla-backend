<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferReceptionResource extends JsonResource
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
      'reception_number' => $this->reception_number,
      'transfer_movement_id' => $this->transfer_movement_id,
      'shipping_guide_id' => $this->shipping_guide_id,
      'warehouse_id' => $this->warehouse_id,
      'reception_date' => $this->reception_date,
      'status' => $this->status,
      'notes' => $this->notes,
      'received_by' => $this->received_by,
      'reviewed_by' => $this->reviewed_by,
      'reviewed_at' => $this->reviewed_at,
      'total_items' => $this->total_items,
      'total_quantity' => $this->total_quantity,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,

      // Relationships
      'transfer_movement' => new InventoryMovementResource($this->whenLoaded('transferMovement')),
      'shipping_guide' => $this->whenLoaded('shippingGuide'),
      'warehouse' => $this->whenLoaded('warehouse'),
      'receiver' => $this->whenLoaded('receiver', function () {
        return [
          'id' => $this->receiver->id,
          'name' => $this->receiver->name,
          'email' => $this->receiver->email,
        ];
      }),
      'reviewer' => $this->whenLoaded('reviewer', function () {
        return $this->reviewer ? [
          'id' => $this->reviewer->id,
          'name' => $this->reviewer->name,
          'email' => $this->reviewer->email,
        ] : null;
      }),
      'details' => TransferReceptionDetailResource::collection($this->whenLoaded('details')),

      // Calculated attributes
      'has_observations' => $this->when($this->details, function () {
        return $this->hasObservations();
      }),
      'total_observed_quantity' => $this->when($this->details, function () {
        return $this->getTotalObservedQuantity();
      }),
      'is_fully_received' => $this->when($this->details, function () {
        return $this->isFullyReceived();
      }),
      'completion_percentage' => $this->when($this->details, function () {
        return $this->getCompletionPercentage();
      }),
    ];
  }
}