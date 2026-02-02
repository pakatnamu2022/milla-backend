<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class UnassignEquipmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'observacion_unassign' => 'required|string',
      'fecha' => 'required|date',
    ];
  }
}
