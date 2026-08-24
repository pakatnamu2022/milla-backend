<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\ApReceivingInspectionDamageResource;
use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Models\ap\comercial\Vehicles;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleInspectionResource extends JsonResource
{
  // Propiedad para recibir el ID de la orden de trabajo
  public $workOrderId = null;

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Obtener el pivot y la orden de trabajo activa
    $pivot = null;
    $workOrder = null;

    // Si se pasó el workOrderId, buscar ese pivot específico (solo los activos, no cancelados)
    if ($this->workOrderId) {
      $pivot = $this->activeWorkOrderPivots()
        ->with(['cancellationRequestedBy', 'cancellationConfirmedBy', 'workOrder.vehicle'])
        ->where('work_order_id', $this->workOrderId)
        ->first();
      $workOrder = $pivot?->workOrder;
    } // Si no hay contexto específico, obtener la orden activa
    else {
      $workOrder = $this->createdByWorkOrder;
      if ($workOrder) {
        $pivot = $this->activeWorkOrderPivots()
          ->with(['cancellationRequestedBy', 'cancellationConfirmedBy'])
          ->where('work_order_id', $workOrder->id)
          ->first();
      }
    }

    return [
      'id' => $this->id,
      'ap_work_order_id' => $workOrder?->id,
      'vehicle_id' => $workOrder?->vehicle_id,
      'vehicle_plate' => $workOrder?->vehicle?->plate,
      'vehicle_vin' => $workOrder?->vehicle?->vin,
      'vehicle_model' => $workOrder?->vehicle?->model?->family?->description,
      'vehicle_brand' => $workOrder?->vehicle?->model?->family?->brand?->name,
      'vehicle_year' => $workOrder?->vehicle?->year,
      'vehicle_color' => $workOrder?->vehicle?->color?->description,
      'engine_type' => $workOrder?->vehicle?->engineType?->description,
      'engine_number' => $workOrder?->vehicle?->engine_number,
      'work_order_correlative' => $workOrder?->correlative,
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
      'inspection_date' => $this->inspection_date,
      'general_observations' => $this->general_observations,
      'inspected_by' => $this->inspected_by,
      'inspected_by_name' => $this->inspectionBy ? $this->inspectionBy->name : null,
      'customer_signature_url' => $this->customer_signature_url,
      'signed_by' => [
        'signer_type' => $this->signer_type,
        'name' => $this->signer_type === 'OWNER'
          ? $this->$workOrder?->vehicle?->customer?->full_name
          : ($this->signer_type === 'CONTACT'
            ? $this->$workOrder?->full_contact_name
            : null),
        'num_doc' => $this->signer_type === 'OWNER'
          ? $this->$workOrder?->vehicle?->customer?->num_doc
          : ($this->signer_type === 'CONTACT'
            ? $this->$workOrder?->num_doc_contact
            : null),
      ],
      'washed' => $this->washed,
      'photo_front_url' => $this->photo_front_url,
      'photo_back_url' => $this->photo_back_url,
      'photo_left_url' => $this->photo_left_url,
      'photo_right_url' => $this->photo_right_url,
      'photo_optional_1_url' => $this->photo_optional_1_url,
      'photo_optional_2_url' => $this->photo_optional_2_url,
      'photo_optional_3_url' => $this->photo_optional_3_url,
      'photo_optional_4_url' => $this->photo_optional_4_url,
      'photo_optional_5_url' => $this->photo_optional_5_url,
      'photo_optional_6_url' => $this->photo_optional_6_url,
      // Detalles de trabajo
      'oil_change' => $this->oil_change,
      'check_level_lights' => $this->check_level_lights,
      'general_lubrication' => $this->general_lubrication,
      'rotation_inspection_cleaning' => $this->rotation_inspection_cleaning,
      'insp_filter_basic_checks' => $this->insp_filter_basic_checks,
      'tire_pressure_inflation_check' => $this->tire_pressure_inflation_check,
      'alignment_balancing' => $this->alignment_balancing,
      'pad_replace_disc_resurface' => $this->pad_replace_disc_resurface,
      'tire_rotation' => $this->tire_rotation,
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

      // Campos de cancelación desde el pivot (work_order_vehicle_inspection)
      'is_cancelled' => $pivot ? (bool)$pivot->is_cancelled : false,
      'cancellation_requested_by' => $pivot?->cancellation_requested_by,
      'cancellation_requested_by_name' => $pivot && $pivot->cancellationRequestedBy ? $pivot->cancellationRequestedBy->name : null,
      'cancellation_confirmed_by' => $pivot?->cancellation_confirmed_by,
      'cancellation_confirmed_by_name' => $pivot && $pivot->cancellationConfirmedBy ? $pivot->cancellationConfirmedBy->name : null,
      'cancellation_requested_at' => $pivot?->cancellation_requested_at,
      'cancellation_confirmed_at' => $pivot?->cancellation_confirmed_at,
      'cancellation_reason' => $pivot?->cancellation_reason,

      // Relationships
      'damages' => ApVehicleInspectionDamagesResource::collection($this->whenLoaded('damages')),
    ];
  }
}
