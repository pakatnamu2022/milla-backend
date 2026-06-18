<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class ResetPasswordByTokenRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'token'    => 'required|string',
            'password' => 'required|string|min:6|max:255|confirmed',
        ];
    }
}
