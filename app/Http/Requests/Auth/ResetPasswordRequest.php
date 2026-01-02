<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class ResetPasswordRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'user_id' => 'required|integer|exists:usr_users,id',
    ];
  }
}
