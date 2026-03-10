<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\ApReceivingInspectionDamageResource;
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
      'ap_work_order_id' => $this->ap_work_order_id,
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
      'washed' => $this->washed,
      'photo_front_url' => $this->photo_front_url,
      'photo_back_url' => $this->photo_back_url,
      'photo_left_url' => $this->photo_left_url,
      'photo_right_url' => $this->photo_right_url,
      // Detalles de trabajo
      'oil_change' => $this->oil_change,
      'check_level_lights' => $this->check_level_lights,
      'general_lubrication' => $this->general_lubrication,
      'rotation_inspection_cleaning' => $this->rotation_inspection_cleaning,
      'insp_filter_basic_checks' => $this->insp_filter_basic_checks,
      'tire_pressure_inflation_check' => $this->tire_pressure_inflation_check,
      'alignment_balancing' => $this->alignment_balancing,
      'pad_replace_disc_resurface' => $this->pad_replace_disc_resurface,
      'other_work_details' => $this->other_work_details,
      // Requerimiento del cliente
      'customer_requirement' => $this->customer_requirement,
      // Explicación de resultados
      'explanation_work_performed' => $this->explanation_work_performed,
      'price_explanation' => $this->price_explanation,
      'confirm_additional_work' => $this->confirm_additional_work,
      'clarification_customer_concerns' => $this->clarification_customer_concerns,
      'exterior_cleaning' => $this->exterior_cleaning,
      'interior_cleaning' => $this->interior_cleaning,
      'keeps_spare_parts' => $this->keeps_spare_parts,
      'valuable_objects' => $this->valuable_objects,
      // Items de cortesía
      'courtesy_seat_cover' => $this->courtesy_seat_cover,
      'paper_floor' => $this->paper_floor,
      // Cancellation fields
      'is_cancelled' => $this->is_cancelled,
      'cancellation_requested_by' => $this->cancellation_requested_by,
      'cancellation_requested_by_name' => $this->cancellationRequestedBy ? $this->cancellationRequestedBy->name : null,
      'cancellation_confirmed_by' => $this->cancellation_confirmed_by,
      'cancellation_confirmed_by_name' => $this->cancellationConfirmedBy ? $this->cancellationConfirmedBy->name : null,
      'cancellation_requested_at' => $this->cancellation_requested_at,
      'cancellation_confirmed_at' => $this->cancellation_confirmed_at,
      'cancellation_reason' => $this->cancellation_reason,

      // Relationships
      'damages' => ApVehicleInspectionDamagesResource::collection($this->whenLoaded('damages')),

      // Trazabilidad: daños registrados en la recepción comercial del mismo vehículo
      'receiving_damages' => $this->getReceivingDamages(),
    ];
  }

  private function getReceivingDamages(): array
  {
    $damages = $this->workOrder?->vehicle
      ?->shippingGuideReceiving
      ?->receivingInspection
      ?->damages;

    if (!$damages) {
      return [];
    }

    return ApReceivingInspectionDamageResource::collection($damages)->resolve();
  }
}
