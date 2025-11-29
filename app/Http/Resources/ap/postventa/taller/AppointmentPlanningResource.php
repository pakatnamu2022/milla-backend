<?php

namespace App\Http\Resources\ap\postventa\taller;

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
      'full_name_client' => $this->full_name_client,
      'email_client' => $this->email_client,
      'phone_client' => $this->phone_client,
      'type_operation_appointment_id' => $this->type_operation_appointment_id,
      'type_planning_id' => $this->type_planning_id,
      'ap_vehicle_id' => $this->ap_vehicle_id,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
