<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StoreUserRoleRequest extends StoreRequest
{
  /**
   * @return string[]
   */
  public function rules(): array
  {
    return [
      'role_id' => 'required|integer|exists:config_roles,id',
      'user_id' => 'required|integer|exists:users,id',
    ];
  }
}
