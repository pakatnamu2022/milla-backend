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
      'item_type' => $this->item_type ?? 'PRODUCTO', // PRODUCTO or SERVICIO
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
      'transfer_movement' => new InventoryMovementResource($this->transferMovement),
      'shipping_guide' => $this->shippingGuide,
      'warehouse' => $this->warehouse,
      'received_name' => $this->receivedByUser ? $this->receivedByUser->name : null,
      'reviewer_name' => $this->reviewedByUser ? $this->reviewedByUser->name : null,
      'details' => TransferReceptionDetailResource::collection($this->details->loadMissing('product')),

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
