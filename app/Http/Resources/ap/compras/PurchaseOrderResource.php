<?php

namespace App\Http\Resources\ap\compras;

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
      'subtotal' => (float) $this->subtotal,
      'igv' => (float) $this->igv,
      'total' => (float) $this->total,
      'discount' => (float) $this->discount,
      'isc' => (float) $this->isc,

      // Relaciones básicas
      'supplier' => [
        'id' => $this->supplier?->id,
        'full_name' => $this->supplier?->full_name,
        'num_doc' => $this->supplier?->num_doc,
      ],

      'currency' => [
        'id' => $this->currency?->id,
        'code' => $this->currency?->code,
        'description' => $this->currency?->description,
      ],

      'warehouse' => [
        'id' => $this->warehouse?->id,
        'description' => $this->warehouse?->description,
        'dyn_code' => $this->warehouse?->dyn_code,
      ],

      // Vehículo (si existe)
      'vehicle' => $this->when($this->vehicle_movement_id, function () {
        return [
          'id' => $this->vehicle?->id,
          'vin' => $this->vehicle?->vin,
          'year' => $this->vehicle?->year,
          'engine_number' => $this->vehicle?->engine_number,
          'model' => [
            'id' => $this->vehicle?->model?->id,
            'code' => $this->vehicle?->model?->code,
            'version' => $this->vehicle?->model?->version,
          ],
          'color' => [
            'id' => $this->vehicle?->color?->id,
            'description' => $this->vehicle?->color?->description,
          ],
          'status' => [
            'id' => $this->vehicle?->status?->id,
            'description' => $this->vehicle?->status?->description,
          ],
        ];
      }),

      // Items de la orden
      'items' => $this->items->map(function ($item) {
        return [
          'id' => $item->id,
          'description' => $item->description,
          'unit_price' => (float) $item->unit_price,
          'quantity' => $item->quantity,
          'total' => (float) $item->total,
          'is_vehicle' => $item->is_vehicle,
          'unit_measurement' => [
            'id' => $item->unitMeasurement?->id,
            'code' => $item->unitMeasurement?->code,
            'description' => $item->unitMeasurement?->description,
          ],
        ];
      }),

      // Estados
      'status' => $this->status,
      'migration_status' => $this->migration_status,
      'invoice_dynamics' => $this->invoice_dynamics,
      'receipt_dynamics' => $this->receipt_dynamics,
      'credit_note_dynamics' => $this->credit_note_dynamics,

      // Fechas
      'migrated_at' => $this->migrated_at?->format('Y-m-d H:i:s'),
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
