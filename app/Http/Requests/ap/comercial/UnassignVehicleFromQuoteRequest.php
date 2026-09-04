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
    ];
  }
}
