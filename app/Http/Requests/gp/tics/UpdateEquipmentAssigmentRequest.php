<?php

namespace App\Http\Requests\gp\tics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentAssigmentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'persona_id' => 'sometimes|required|exists:rrhh_persona,id',
      'fecha' => 'sometimes|required|date',
      'status_id' => 'nullable|integer',
      'conformidad' => 'nullable|string',
      'fecha_conformidad' => 'nullable|date',
      'items' => 'sometimes|array|min:1',
      'items.*.id' => 'nullable|integer',
      'items.*.equipo_id' => 'required|exists:help_equipos,id',
      'items.*.observacion' => 'nullable|string',
      'items.*.status_id' => 'nullable|integer',
      'items.*.observacion_dev' => 'nullable|string',
    ];
  }
}
