<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class StoreEquipmentAssigmentRequest extends StoreRequest
{
  public function prepareForValidation()
  {
    return $this->merge([
      'fecha' => $this->fecha ?? now()->toDateString(),
      'write_id' => auth()->user()->id
    ]);
  }

  public function rules(): array
  {
    return [
      'persona_id' => 'required|exists:rrhh_persona,id',
      'fecha' => 'required|date',
      'observacion' => 'nullable|string',
      'write_id' => 'nullable|exists:usr_users,id',
      'items' => 'required|array|min:1',
      'items.*.equipo_id' => 'required|exists:help_equipos,id',
      'items.*.observacion' => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'persona_id' => 'persona',
      'fecha' => 'fecha',
      'observacion' => 'observacion',
      'write_id' => 'usuario',
      'items' => 'items',
      'items.*.equipo_id' => 'equipo',
      'items.*.observacion' => 'observacion',
    ];
  }
}
