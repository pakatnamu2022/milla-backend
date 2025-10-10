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
      'status' => $this->status,
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

      // Relations
      'ap_models_vn' => $this->model->code ?? null,
      'vehicle_color' => $this->color->description ?? null,
      'supplier_order_type' => $this->supplierType->description ?? null,
      'engine_type' => $this->engineType->description ?? null,
      'sede' => $this->sede->abreviatura ?? null,
      'ap_vehicle_status' => $this->vehicleStatus->code ?? null,
      'color_vehicle_status' => $this->vehicleStatus->color ?? null,
    ];
  }
}
