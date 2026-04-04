<?php

namespace App\Http\Requests\gp\tics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentAssigmentRequest extends FormRequest
{
  public function prepareForValidation()
  {
    return $this->merge([
      'write_id' => auth()->user()->id
    ]);
  }

  public function rules(): array
  {
    return [
      'persona_id' => 'sometimes|required|exists:rrhh_persona,id',
      'status_id' => 'nullable|integer',
      'write_id' => 'required|exists:usr_users,id',
      'items' => 'sometimes|array|min:1',
      'items.*.id' => 'nullable|integer',
      'items.*.equipo_id' => 'required|exists:help_equipos,id',
      'items.*.observacion' => 'nullable|string',
      'items.*.status_id' => 'nullable|integer',
      'items.*.observacion_dev' => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'persona_id' => 'persona',
      'observacion' => 'observacion',
      'write_id' => 'usuario',
      'items' => 'items',
      'items.*.equipo_id' => 'equipo',
      'items.*.observacion' => 'observacion',
    ];
  }
}
