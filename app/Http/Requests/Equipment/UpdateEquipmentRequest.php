<?php

namespace App\Http\Requests\Equipment;

use App\Http\Requests\StoreRequest;

class UpdateEquipmentRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'equipo' => 'sometimes|required|string|max:255',
            'tipo_equipo_id' => 'sometimes|required|integer|exists:help_tipo_equipo,id',
            'marca_modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'detalle' => 'nullable|string|max:500',
            'ram' => 'nullable|string|max:50',
            'almacenamiento' => 'nullable|string|max:50',
            'procesador' => 'nullable|string|max:100',
            'stock_actual' => 'sometimes|required|integer|min:0',
            'estado_uso' => 'sometimes|required|in:NUEVO,USADO',
            'sede_id' => 'sometimes|required|integer|exists:config_sede,id',
            'status_id' => 'sometimes|required|integer|exists:config_status,id|in:28,29',
            'pertenece_sede' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'equipo' => 'nombre del equipo',
            'tipo_equipo_id' => 'tipo de equipo',
            'sede_id' => 'sede',
            'status_id' => 'estado del equipo',
            'stock_actual' => 'cantidad en stock',
            'estado_uso' => 'estado de uso',
            'marca_modelo' => 'marca y modelo',
            'serie' => 'número de serie',
            'detalle' => 'detalles adicionales',
            'ram' => 'memoria RAM',
            'almacenamiento' => 'almacenamiento',
            'procesador' => 'procesador',
        ];
    }
}
