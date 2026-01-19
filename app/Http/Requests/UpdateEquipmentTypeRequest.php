<?php

namespace App\Http\Requests;

class UpdateEquipmentTypeRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'equipo' => 'sometimes|required|string|max:255',
            'name' => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'equipo' => 'tipo de equipo',
            'name' => 'nombre',
        ];
    }
}
