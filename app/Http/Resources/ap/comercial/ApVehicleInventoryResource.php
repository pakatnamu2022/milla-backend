<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleInventoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,

      'ap_vehicle_id' => $this->ap_vehicle_id,
      'vehicle' => $this->vehicle ? [
        'id' => $this->vehicle->id,
        'vin' => $this->vehicle->vin,
        'plate' => $this->vehicle->plate,
        'year' => $this->vehicle->year,
        'ap_vehicle_status_id' => $this->vehicle->ap_vehicle_status_id,
        'vehicle_status' => $this->vehicle->vehicleStatus?->description,
        'warehouse_id' => $this->vehicle->warehouse_id,
        'warehouse' => $this->vehicle->warehouse ? [
          'id' => $this->vehicle->warehouse->id,
          'description' => $this->vehicle->warehouse->description,
          'sede_id' => $this->vehicle->warehouse->sede_id,
          'sede' => $this->vehicle->warehouse->sede?->abreviatura,
        ] : null,
      ] : null,

      'inventory_warehouse_id' => $this->inventory_warehouse_id,
      'inventory_warehouse' => $this->inventoryWarehouse ? [
        'id' => $this->inventoryWarehouse->id,
        'description' => $this->inventoryWarehouse->description,
        'sede_id' => $this->inventoryWarehouse->sede_id,
        'sede' => $this->inventoryWarehouse->sede?->abreviatura,
      ] : null,

      'vin' => $this->vin,

      'vehicle_color_id' => $this->vehicle_color_id,
      'color' => $this->color?->description,

      'brand_id' => $this->brand_id,
      'brand' => $this->brand?->name,

      'model_id' => $this->model_id,
      'model' => $this->model ? [
        'id' => $this->model->id,
        'version' => $this->model->version,
        'family' => $this->model->family?->description,
      ] : null,

      'year' => $this->year,

      'fuel_type_id' => $this->fuel_type_id,
      'fuel_type' => $this->fuelType?->description,

      'adjudication_date' => $this->adjudication_date?->format('Y-m-d'),
      'days' => $this->days,
      'limit_date' => $this->limit_date?->format('Y-m-d'),
      'reception_date' => $this->reception_date?->format('Y-m-d'),

      'is_location_confirmed' => $this->is_location_confirmed,
      'is_evaluated' => $this->is_evaluated,
      'evaluated_at' => $this->evaluated_at?->format('Y-m-d H:i:s'),
      'evaluated_by' => $this->evaluated_by,
      'evaluator' => $this->evaluatedBy ? [
        'id' => $this->evaluatedBy->id,
        'name' => $this->evaluatedBy->name,
      ] : null,

      'status' => $this->status,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
