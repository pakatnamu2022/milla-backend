<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'ap_vehicle_id'    => $this->ap_vehicle_id,
      'vehicle'          => $this->vehicle ? [
        'id'     => $this->vehicle->id,
        'vin'    => $this->vehicle->vin,
        'plate'  => $this->vehicle->plate,
        'year'   => $this->vehicle->year,
        'model'  => $this->vehicle->model?->version,
        'brand'  => $this->vehicle->model?->family?->brand?->name,
        'color'  => $this->vehicle->color?->description,
        'status' => $this->vehicle->vehicleStatus?->description,
        'warehouse'   => $this->vehicle->warehouse?->description,
        'sede'        => $this->vehicle->warehouse?->sede?->abreviatura,
      ] : null,
      'worker_id'        => $this->worker_id,
      'worker'          => $this->worker ? [
        'id'   => $this->worker->id,
        'name' => $this->worker->nombre_completo,
      ] : null,
      'assigned_date'    => $this->assigned_date?->format('Y-m-d'),
      'observation'      => $this->observation,
      'dyn_series'       => $this->dyn_series,
      'migration_status' => $this->migration_status,
      'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
    ];
  }
}
