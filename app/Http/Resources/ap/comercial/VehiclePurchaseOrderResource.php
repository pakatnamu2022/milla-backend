<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiclePurchaseOrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      // Vehicle
      'vin' => $this->vin,
      'year' => $this->year,
      'engine_number' => $this->engine_number,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'vehicle_color_id' => $this->vehicle_color_id,
      'supplier_order_type_id' => $this->supplier_order_type_id,
      'engine_type_id' => $this->engine_type_id,
      'ap_vehicle_status_id' => $this->ap_vehicle_status_id,
      'sede_id' => $this->sede_id,

      // Invoice
      'invoice_series' => $this->invoice_series,
      'invoice_number' => $this->invoice_number,
      'emission_date' => $this->emission_date,
      'unit_price' => $this->unit_price,
      'discount' => $this->discount,
      'subtotal' => $this->subtotal,
      'igv' => $this->igv,
      'total' => $this->total,
      'supplier_id' => $this->supplier_id,
      'currency_id' => $this->currency_id,
      'exchange_rate_id' => $this->exchange_rate_id,

      // Guide
      'number' => $this->number,
      'number_guide' => $this->number_guide,
      'warehouse_id' => $this->warehouse_id,
      'warehouse_physical_id' => $this->warehouse_physical_id,
      'invoice_dynamics' => $this->invoice_dynamics,
      'receipt_dynamics' => $this->receipt_dynamics,
      'credit_note_dynamics' => $this->credit_note_dynamics,
      'po_status' => $this->status, // Boolean: true=activa, false=anulada

      // Relations
      'supplier' => $this->supplier->full_name,
      'supplier_num_doc' => $this->supplier->num_doc,
      'currency' => $this->currency->name,
      'currency_code' => $this->currency->code,
      'model_code' => $this->model_code,
      'vehicle_color' => $this->color->description,
      'supplier_order_type' => $this->supplierType->description,
      'engine_type' => $this->engineType->description,
      'sede' => $this->sede->abreviatura,
      'status' => $this->vehicleStatus->description,
      'status_color' => $this->vehicleStatus->color,
      'warehouse' => $this->warehouse->description,
      'warehouse_physical' => $this->warehousePhysical?->description,
      'taxClassType' => $this->supplier->taxClassType->tax_class,
      'movements' => VehicleMovementResource::collection($this->movements),
    ];
  }
}
