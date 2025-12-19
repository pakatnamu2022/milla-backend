<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexApExhibitionVehiclesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'vehicle_id' => 'nullable|integer|exists:ap_vehicles,id',
      'advisor_id' => 'nullable|integer|exists:rrhh_persona,id',
      'propietario_id' => 'nullable|integer|exists:business_partners,id',
      'ubicacion_id' => 'nullable|integer|exists:warehouse,id',
      'status' => 'nullable|boolean',
      'ap_vehicle_status_id' => 'nullable|array',
      'ap_vehicle_status_id.*' => 'integer|exists:ap_vehicle_status,id',
    ];
  }
}
