<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class UpdateApAssignmentLeadershipRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => 'nullable|integer|min:2000|max:2100',
      'month' => 'nullable|integer|min:1|max:12',
      'boss_id' => 'nullable|exists:rrhh_persona,id',
      'assigned_workers' => 'nullable|array|min:1',
      'assigned_workers.*' => 'nullable|exists:rrhh_persona,id',
      'status' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'year.integer' => 'El campo año debe ser un número entero.',
      'year.min' => 'El campo año no puede ser menor a 2000.',
      'year.max' => 'El campo año no puede ser mayor a 2100.',

      'month.integer' => 'El campo mes debe ser un número entero.',
      'month.min' => 'El campo mes no puede ser menor a 1.',
      'month.max' => 'El campo mes no puede ser mayor a 12.',

      'boss_id.exists' => 'El jefe de venta proporcionado no existe.',

      'workers.array' => 'El campo asesores debe ser un arreglo.',
      'workers.min' => 'Debe proporcionar al menos un asesor.',

      'assigned_workers.*.integer' => 'Cada asesor debe ser un ID entero válido.',
      'assigned_workers.*.exists' => 'Uno o más IDs de asesores proporcionados no existen.',
    ];
  }
}
