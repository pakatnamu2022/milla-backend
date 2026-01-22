<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexApVehicleDeliveryRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'real_delivery_date' => 'nullable|array|min:2|max:2',
      'real_delivery_date.*' => 'nullable|date',
    ];
  }
}
