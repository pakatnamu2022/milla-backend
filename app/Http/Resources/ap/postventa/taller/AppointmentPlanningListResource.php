<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AppointmentPlanningListResource - Resource optimizado para listados
 */
class AppointmentPlanningListResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      // Campos principales
      'id' => $this->id,
      'full_name_client' => $this->full_name_client,
      'plate' => $this->vehicle?->plate,
      'type_planning_id' => $this->type_planning_id,
      'type_planning_name' => $this->typePlanning?->description,
      'type_operation_appointment_id' => $this->type_operation_appointment_id,
      'type_operation_appointment_name' => $this->typeOperationAppointment?->description,
      'email_client' => $this->email_client,
      'phone_client' => $this->phone_client,
      'ap_vehicle_id' => $this->ap_vehicle_id,
      // Vehículo
      'vehicle' => [
        'id' => $this->vehicle?->id,
        'vin' => $this->vehicle?->vin,
        'plate' => $this->vehicle?->plate,
        'brand' => $this->vehicle?->model?->family?->brand?->name,
        'model' => $this->vehicle?->model?->version,
        'year' => $this->vehicle?->year,
        'color' => $this->vehicle?->color?->description,
        'engine_type' => $this->vehicle?->engineType?->description,
        'engine_number' => $this->vehicle?->engine_number,
      ],

      // Fechas y horas de atención
      'date_appointment' => $this->date_appointment,
      'time_appointment' => $this->time_appointment,

      // Fechas y horas de entrega
      'delivery_date' => $this->delivery_date,
      'delivery_time' => $this->delivery_time,

      // Descripción
      'description' => $this->description,

      // Usuarios
      'advisor_name' => $this->advisor?->nombre_completo,
      'created_by_name' => $this->creator?->name,

      // Flag
      'is_taken' => (bool)$this->is_taken,

      //Orden de Trabajo
      'work_order_id' => $this->workOrder?->id,
      'work_order_correlative' => $this->workOrder?->correlative,
    ];
  }
}
