<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StoreManyUserSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'assignments' => 'required|array|min:1',
      'assignments.*.user_id' => 'required|exists:usr_users,id',
      'assignments.*.sede_id' => 'required|exists:config_sede,id',
      'assignments.*.status' => 'sometimes|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'assignments.required' => 'Las asignaciones son requeridas',
      'assignments.array' => 'Las asignaciones deben ser un arreglo',
      'assignments.min' => 'Debe enviar al menos una asignación',
      'assignments.*.user_id.required' => 'El usuario es requerido en cada asignación',
      'assignments.*.user_id.exists' => 'Uno o más usuarios no existen',
      'assignments.*.sede_id.required' => 'La sede es requerida en cada asignación',
      'assignments.*.sede_id.exists' => 'Una o más sedes no existen',
      'assignments.*.status.boolean' => 'El estado debe ser verdadero o falso',
    ];
  }
}
