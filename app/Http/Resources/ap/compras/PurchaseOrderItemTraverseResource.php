<?php

namespace App\Http\Resources\ap\compras;

use App\Http\Resources\ap\maestroGeneral\UnitMeasurementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para items de Purchase Orders disponibles para travesía
 * Incluye información adicional como saldo disponible y datos de la Purchase Order
 */
class PurchaseOrderItemTraverseResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Calcular saldo disponible
    $saldoDisponible = $this->quantity - $this->quantity_available_traverse;

    return [
      'id' => $this->id,
      'purchase_order_item_id' => $this->id,

      // Información del item
      'description' => $this->description ?? null,
      'unit_price' => (float)$this->unit_price,
      'quantity' => (float)$this->quantity,
      'quantity_available_traverse' => (float)$this->quantity_available_traverse,
      'saldo_disponible' => (float)$saldoDisponible,
      'total' => (float)$this->total,

      // Información del producto
      'product_id' => $this->product_id ?? null,
      'product_name' => $this->product->name ?? null,
      'product_code' => $this->product->code ?? null,
      'product_dyn_code' => $this->product->dyn_code ?? null,

      // Unidad de medida
      'unit_measurement' => UnitMeasurementResource::make($this->unitMeasurement) ?? null,

      // Información de la Purchase Order
      'purchase_order' => [
        'id' => $this->purchaseOrder->id ?? null,
        'number' => $this->purchaseOrder->number ?? null,
        'emission_date' => $this->purchaseOrder->emission_date ?? null,
        'status' => $this->purchaseOrder->status ?? null,
        'migration_status' => $this->purchaseOrder->migration_status ?? null,
        'invoice_dynamics' => $this->purchaseOrder->invoice_dynamics ?? null,
        'receipt_dynamics' => $this->purchaseOrder->receipt_dynamics ?? null,
        'supplier' => [
          'id' => $this->purchaseOrder->supplier->id ?? null,
          'full_name' => $this->purchaseOrder->supplier->full_name ?? null,
        ],
      ],
    ];
  }
}