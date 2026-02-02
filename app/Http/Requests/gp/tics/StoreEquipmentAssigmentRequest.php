<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class StoreEquipmentAssigmentRequest extends StoreRequest
{
  public function prepareForValidation()
  {
    return $this->merge([
      'fecha' => $this->fecha ?? now()->toDateString(),
    ]);
  }

  public function rules(): array
  {
    return [
      'persona_id' => 'required|exists:rrhh_persona,id',
      'fecha' => 'required|date',
      'observacion' => 'nullable|string',
      'items' => 'required|array|min:1',
      'items.*.equipo_id' => 'required|exists:help_equipos,id',
      'items.*.observacion' => 'nullable|string',
    ];
  }
}
