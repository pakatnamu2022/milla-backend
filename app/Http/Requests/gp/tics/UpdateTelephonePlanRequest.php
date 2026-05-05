<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class UpdateTelephonePlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'sometimes|required|string|max:255',
      'price' => 'sometimes|required|numeric|min:0',
      'description' => 'nullable|string',
    ];
  }
}
