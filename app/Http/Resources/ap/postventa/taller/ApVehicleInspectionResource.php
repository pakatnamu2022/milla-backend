<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Models\ap\comercial\Vehicles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleInspectionResource extends JsonResource
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
      'vehicle_id' => $this->workOrder?->vehicle_id,
      'vehicle_plate' => $this->workOrder?->vehicle->plate,
      'vehicle_vin' => $this->workOrder?->vehicle->vin,
      'work_order_correlative' => $this->workOrder ? $this->workOrder->correlative : null,
      'mileage' => $this->mileage,
      'fuel_level' => $this->fuel_level,
      'oil_level' => $this->oil_level,
      'dirty_unit' => $this->dirty_unit,
      'unit_ok' => $this->unit_ok,
      'title_deed' => $this->title_deed,
      'soat' => $this->soat,
      'moon_permits' => $this->moon_permits,
      'service_card' => $this->service_card,
      'owner_manual' => $this->owner_manual,
      'key_ring' => $this->key_ring,
      'wheel_lock' => $this->wheel_lock,
      'safe_glasses' => $this->safe_glasses,
      'radio_mask' => $this->radio_mask,
      'lighter' => $this->lighter,
      'floors' => $this->floors,
      'seat_cover' => $this->seat_cover,
      'quills' => $this->quills,
      'antenna' => $this->antenna,
      'glasses_wheel' => $this->glasses_wheel,
      'emblems' => $this->emblems,
      'spare_tire' => $this->spare_tire,
      'fluid_caps' => $this->fluid_caps,
      'tool_kit' => $this->tool_kit,
      'jack_and_lever' => $this->jack_and_lever,
      'inspection_date' => $this->inspection_date->format('Y-m-d'),
      'general_observations' => $this->general_observations,
      'inspected_by' => $this->inspected_by,
      'inspected_by_name' => $this->inspectionBy ? $this->inspectionBy->name : null,
      'customer_signature_url' => $this->customer_signature_url,
      // Relationships
      'damages' => ApVehicleInspectionDamagesResource::collection($this->whenLoaded('damages')),
    ];
  }
}
