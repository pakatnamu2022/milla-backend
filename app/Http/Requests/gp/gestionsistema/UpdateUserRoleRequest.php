<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class UpdateUserRoleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'role_id' => 'required|integer|exists:config_roles,id',
    ];
  }
}
