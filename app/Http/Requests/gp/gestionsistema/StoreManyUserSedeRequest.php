<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StoreManyUserSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'user_id' => 'required|exists:usr_users,id',
      'sede_ids' => 'required|array',
      'sede_ids.*' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'user_id.required' => 'El ID del usuario es requerido',
      'user_id.exists' => 'El usuario no existe',
      'sede_ids.required' => 'Las sedes son requeridas',
      'sede_ids.array' => 'Las sedes deben ser un arreglo',
      'sede_ids.*.required' => 'Cada sede es requerida',
      'sede_ids.*.integer' => 'El ID de sede debe ser un número',
      'sede_ids.*.exists' => 'Una o más sedes no existen',
    ];
  }
}
