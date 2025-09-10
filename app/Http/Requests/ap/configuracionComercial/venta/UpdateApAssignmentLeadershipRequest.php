<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class UpdateApAssignmentLeadershipRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'boss_id' => 'required|exists:rrhh_persona,id',
      'assigned_workers' => 'required|array|min:1',
      'assigned_workers.*' => 'integer|exists:rrhh_persona,id',
    ];
  }

  public function messages(): array
  {
    return [
      'boss_id.required' => 'El campo boss_id es obligatorio.',
      'boss_id.exists' => 'El jefe proporcionado no existe.',

      'workers.required' => 'El campo asesores es obligatorio.',
      'workers.array' => 'El campo asesores debe ser un arreglo.',
      'workers.min' => 'Debe proporcionar al menos un asesor.',

      'assigned_workers.*.integer' => 'Cada asesor debe ser un ID entero válido.',
      'assigned_workers.*.exists' => 'Uno o más IDs de asesores proporcionados no existen.',
    ];
  }
}
