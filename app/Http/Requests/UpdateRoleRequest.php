<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                Rule::unique('config_roles', 'nombre')->where('status_deleted', 1)
                    ->ignore($this->route('role'))
            ],
            'descripcion' => [
                'required',
                'string',
                Rule::unique('config_roles', 'descripcion')->where('status_deleted', 1)
                    ->ignore($this->route('role'))
            ],
        ];
    }
}
