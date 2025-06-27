<?php

namespace App\Http\Requests\Equipment;

use App\Http\Requests\StoreRequest;

class StoreEquipmentRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'equipo' => 'required|string|max:255',
            'tipo_equipo_id' => 'required|exists:help_tipo_equipo,id',
            'marca_modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'detalle' => 'nullable|string|max:500',
            'ram' => 'nullable|string|max:50',
            'almacenamiento' => 'nullable|string|max:50',
            'procesador' => 'nullable|string|max:100',
            'stock_actual' => 'required|integer|min:0',
            'estado_uso' => 'required|in:NUEVO,USADO',
            'sede_id' => 'required|exists:config_sede,id',
            'status_id' => 'required|exists:config_status,id|in:28,29',
            'pertenece_sede' => 'boolean',
        ];
    }
}
