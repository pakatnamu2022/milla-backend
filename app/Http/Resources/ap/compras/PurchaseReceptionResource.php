<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceptionResource extends JsonResource
{
  /**
   * Traducciones para reception_type
   */
  private function translateReceptionType(?string $type): ?string
  {
    $translations = [
      'COMPLETE' => 'Completa',
      'PARTIAL' => 'Parcial',
    ];

    return $type ? ($translations[$type] ?? $type) : null;
  }

  /**
   * Traducciones para status
   */
  private function translateStatus(?string $status): ?string
  {
    $translations = [
      'APPROVED' => 'Aprobado',
      'PARTIAL' => 'Parcial',
      'INCOMPLETE' => 'Incompleto',
    ];

    return $status ? ($translations[$status] ?? $status) : null;
  }

  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'reception_number' => $this->reception_number,
      'reception_date' => $this->reception_date->format('Y-m-d'),
      'shipping_guide_number' => $this->shipping_guide_number,
      'reception_type' => $this->translateReceptionType($this->reception_type),
      'notes' => $this->notes ?? "",
      'received_by' => $this->received_by,
      'received_by_user_name' => $this->receivedByUser ? $this->receivedByUser->name : null,
      'total_items' => $this->total_items,
      'total_quantity' => $this->total_quantity,
      'purchase_order_id' => $this->purchase_order_id,
      'warehouse_id' => $this->warehouse_id,
      'status' => $this->translateStatus($this->status),

      // Relationships
      'purchase_order' => new PurchaseOrderResource($this->purchaseOrder),
      'warehouse' => new WarehouseResource($this->warehouse),
      'details' => PurchaseReceptionDetailResource::collection($this->details->load('product')),
    ];
  }
}
