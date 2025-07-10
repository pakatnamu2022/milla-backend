<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreRoleRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                Rule::unique('config_roles', 'nombre')->where('status_deleted', 1)
            ],
            'descripcion' => [
                'required',
                'string',
                Rule::unique('config_roles', 'descripcion')->where('status_deleted', 1)
            ],
        ];
    }
}
