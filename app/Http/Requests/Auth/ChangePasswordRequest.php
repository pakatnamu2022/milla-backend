<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class ChangePasswordRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'current_password' => 'required|string',
      'new_password' => 'required|string|min:6|max:255|confirmed',
    ];
  }
}
