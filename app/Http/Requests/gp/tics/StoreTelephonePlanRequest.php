<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class StoreTelephonePlanRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'price' => 'required|numeric|min:0',
      'description' => 'nullable|string',
    ];
  }
}
