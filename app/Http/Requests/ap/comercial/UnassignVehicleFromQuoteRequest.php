<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UnassignVehicleFromQuoteRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => [
        'required',
        'integer',
        Rule::exists('ap_vehicles', 'id')->whereNull('deleted_at'),
      ],
      // Si es true, además de desasignar el vehículo se cierra por completo la
      // solicitud (status = 0) y la oportunidad asociada (estado CLOSED).
      'close' => ['sometimes', 'boolean'],
    ];
  }
}
