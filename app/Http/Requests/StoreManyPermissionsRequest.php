<?php

namespace App\Http\Requests;

class StoreManyPermissionsRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            '*.vista_id' => 'required|exists:config_vista,id',
            '*.ver' => 'required|boolean',
            '*.crear' => 'required|boolean',
            '*.editar' => 'required|boolean',
            '*.anular' => 'required|boolean',
        ];
    }
}
