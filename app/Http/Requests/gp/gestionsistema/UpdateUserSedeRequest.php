<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class UpdateUserSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'user_id' => 'sometimes|exists:usr_users,id',
      'sede_id' => 'sometimes|exists:config_sede,id',
      'status' => 'sometimes|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'user_id.exists' => 'El usuario no existe',
      'sede_id.exists' => 'La sede no existe',
      'status.boolean' => 'El estado debe ser verdadero o falso',
    ];
  }
}
