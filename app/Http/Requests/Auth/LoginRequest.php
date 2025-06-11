<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class
LoginRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string|min:6|max:255',
        ];
    }
}
