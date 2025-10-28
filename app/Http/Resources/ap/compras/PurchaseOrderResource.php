<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
      'number' => $this->number,
      'number_guide' => $this->number_guide,

      // Factura
      'invoice_series' => $this->invoice_series,
      'invoice_number' => $this->invoice_number,
      'emission_date' => $this->emission_date?->format('Y-m-d'),
      'due_date' => $this->due_date?->format('Y-m-d'),

      // Valores
      'subtotal' => (float)$this->subtotal,
      'igv' => (float)$this->igv,
      'total' => (float)$this->total,
      'discount' => (float)$this->discount,
      'isc' => (float)$this->isc,

      // Relaciones básicas
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'supplier' => $this->supplier->full_name,
      'supplier_num_doc' => $this->supplier->num_doc,
      'supplier_order_type' => $this->supplierOrderType->description,

      'currency' => $this->currency->name,
      'currency_code' => $this->currency->code,
      'warehouse' => $this->warehouse->description,

      // Vehículo (si existe)
      'vehicle' => VehiclesResource::make($this->vehicle),

      // Items de la orden
      'items' => PurchaseOrderItemResource::collection($this->items),

      // ID
      'supplier_id' => $this->supplier_id,
      'supplier_order_type_id' => $this->supplier_order_type_id,
      'currency_id' => $this->currency_id,
      'warehouse_id' => $this->warehouse_id,

      // Estados
      'resent' => (bool)$this->resent,
      'status' => (bool)$this->status,
      'migration_status' => $this->migration_status,
      'invoice_dynamics' => $this->invoice_dynamics,
      'receipt_dynamics' => $this->receipt_dynamics,
      'credit_note_dynamics' => $this->credit_note_dynamics,
      'vehicleMovement' => VehicleMovementResource::make($this->vehicleMovement),

      // Fechas
      'migrated_at' => $this->migrated_at?->format('Y-m-d H:i:s'),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
