<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\VehiclesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentPlanningResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'description' => $this->description,
      'delivery_date' => $this->delivery_date,
      'delivery_time' => $this->delivery_time,
      'date_appointment' => $this->date_appointment,
      'time_appointment' => $this->time_appointment,
      'num_doc_client' => $this->num_doc_client,
      'full_name_client' => $this->full_name_client,
      'email_client' => $this->email_client,
      'phone_client' => $this->phone_client,
      'type_operation_appointment_id' => $this->type_operation_appointment_id,
      'type_planning_id' => $this->type_planning_id,
      'type_planning_name' => $this->typePlanning ? $this->typePlanning->description : null,
      'ap_vehicle_id' => $this->ap_vehicle_id,
      'vehicle' => VehiclesResource::make($this->vehicle),
      'advisor_id' => $this->advisor_id,
      'plate' => $this->vehicle ? $this->vehicle->plate : null,
      'sede_id' => $this->sede_id,
      'sede_name' => $this->sede ? $this->sede->abreviatura : null,
      'is_taken' => $this->is_taken,
    ];
  }
}
