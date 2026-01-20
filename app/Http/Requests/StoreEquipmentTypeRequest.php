<?php

namespace App\Http\Requests;

class StoreEquipmentTypeRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'equipo' => 'required|string|max:255',
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
