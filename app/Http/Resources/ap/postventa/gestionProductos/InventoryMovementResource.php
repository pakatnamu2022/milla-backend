<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Resources\ap\compras\PurchaseReceptionResource;
use App\Http\Resources\ap\postventa\taller\ApOrderQuotationsResource;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\postventa\taller\ApOrderQuotations;
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
      'item_type' => $this->item_type ?? 'PRODUCTO', // PRODUCTO or SERVICIO
      'movement_date' => $this->movement_date,
      'is_inbound' => $this->is_inbound, // true si es ingreso, false si no
      'is_outbound' => $this->is_outbound, // true si es salida, false si no
      'warehouse_origin_id' => $this->warehouse_id,
      'warehouse_code' => $this->warehouse ? $this->warehouse->dyn_code : null,
      'warehouse_origin' => $this->warehouse,
      'warehouse_destination_id' => $this->warehouse_destination_id,
      'warehouse_destination_code' => $this->warehouseDestination ? $this->warehouseDestination->dyn_code : null,
      'warehouse_destination' => $this->warehouseDestination,
      'reference_type' => $this->reference_type,
      'reference_id' => $this->reference_id,
      'reference' => $this->formatReference(),
      'user_id' => $this->user_id,
      'user_name' => $this->user ? $this->user->name : null,
      'reason_in_out_id' => $this->reason_in_out_id,
      'reason_in_out' => $this->reasonInOut,
      'status' => $this->status,
      'notes' => $this->notes,
      'total_items' => $this->total_items,
      'total_quantity' => $this->total_quantity,
      'details' => InventoryMovementDetailResource::collection($this->whenLoaded('details')),
      // Calculated fields for kardex (only present when using getProductMovementHistory)
      'quantity_in' => $this->quantity_in ?? null,
      'quantity_out' => $this->quantity_out ?? null,
      'balance' => $this->balance ?? null,
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }

  /**
   * Format reference field with appropriate Resource based on reference_type
   */
  private function formatReference()
  {
    if (!$this->reference) {
      return null;
    }

    // Map reference_type to corresponding Resource class
    $resourceMap = [
      ShippingGuides::class => ShippingGuidesResource::class,
      ApOrderQuotations::class => ApOrderQuotationsResource::class,
      PurchaseReception::class => PurchaseReceptionResource::class,
    ];

    $resourceClass = $resourceMap[$this->reference_type] ?? null;

    if (!$resourceClass) {
      return $this->reference;
    }

    // Load specific relations based on reference type
    $relationsMap = [
      ApOrderQuotations::class => ['advancesOrderQuotation'],
    ];

    if (isset($relationsMap[$this->reference_type])) {
      $this->reference->loadMissing($relationsMap[$this->reference_type]);
    }

    return new $resourceClass($this->reference);
  }
}
