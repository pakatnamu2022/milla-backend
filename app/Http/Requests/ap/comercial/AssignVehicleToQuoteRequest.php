<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class AssignVehicleToQuoteRequest extends StoreRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'ap_vehicle_id' => [
        'required',
        'integer',
        Rule::exists('ap_vehicles', 'id')->whereNull('deleted_at'),
        Rule::unique('purchase_request_quote', 'ap_vehicle_id')->whereNull('deleted_at'),
      ],
    ];
  }
}
