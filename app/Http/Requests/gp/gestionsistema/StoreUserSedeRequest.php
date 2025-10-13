<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StoreUserSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'user_id' => 'required|exists:usr_users,id',
      'sede_id' => 'required|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'user_id.required' => 'El usuario es requerido',
      'user_id.exists' => 'El usuario no existe',
      'sede_id.required' => 'La sede es requerida',
      'sede_id.exists' => 'La sede no existe',
    ];
  }
}
