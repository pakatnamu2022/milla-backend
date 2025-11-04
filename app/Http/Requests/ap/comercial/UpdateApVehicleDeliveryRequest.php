<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleDeliveryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'status_delivery' => [
        'nullable',
        'string',
        'in:pending,completed',
      ],
      'status_wash' => [
        'nullable',
        'string',
        'in:pending,completed',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'status_delivery.in' => 'The status_delivery field must be either pending or completed.',
      'status_wash.in' => 'The status_wash field must be either pending or completed.',
    ];
  }
}
