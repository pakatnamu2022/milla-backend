<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class ForgotPasswordRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string|exists:usr_users,username',
        ];
    }
}
