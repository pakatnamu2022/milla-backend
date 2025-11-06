<?php

namespace App\Http\Resources\ap\comercial;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleBrandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiclesResource extends JsonResource
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
      'vin' => $this->vin,
      'year' => $this->year,
      'engine_number' => $this->engine_number,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'vehicle_color_id' => $this->vehicle_color_id,
      'engine_type_id' => $this->engine_type_id,
      'ap_vehicle_status_id' => $this->ap_vehicle_status_id,
      'vehicle_color' => $this->color->description,
      'engine_type' => $this->engineType->description,
      'status' => $this->status,
      'vehicle_status' => $this->vehicleStatus->description,
      'status_color' => $this->vehicleStatus->color,
      'warehouse_physical_id' => $this->warehouse_physical_id,
      'warehouse_physical' => $this->warehousePhysical?->description,
      'model' => ApModelsVnResource::make($this->model),
      'movements' => VehicleMovementResource::collection($this->vehicleMovements),
    ];
  }
}
