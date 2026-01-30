<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class UpdateUserRoleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'role_id' => 'sometimes|required|integer|exists:config_roles,id',
      'user_id' => 'sometimes|required|integer|exists:users,id',
    ];
  }
}
