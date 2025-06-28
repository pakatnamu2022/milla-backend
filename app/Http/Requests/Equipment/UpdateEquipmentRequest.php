<?php

namespace App\Http\Requests\Equipment;

use App\Http\Requests\StoreRequest;

class UpdateEquipmentRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'tipo_equipo_id' => 'sometimes|required|integer|exists:help_tipo_equipo,id',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'detalle' => 'nullable|string|max:500',
            'ram' => 'nullable|string|max:50',
            'almacenamiento' => 'nullable|string|max:50',
            'procesador' => 'nullable|string|max:100',
            'stock_actual' => 'sometimes|required|integer|min:0',
            'estado_uso' => 'sometimes|required|in:NUEVO,USADO',
            'sede_id' => 'sometimes|required|integer|exists:config_sede,id',
            'pertenece_sede' => 'boolean|sometimes',
            'tipo_adquisicion' => 'nullable|string|in:CONTRATO,COMPRA',
            'factura' => 'nullable|string|max:255|required_if:tipo_adquisicion,COMPRA',
            'contrato' => 'nullable|string|max:255|required_if:tipo_adquisicion,CONTRATO',
            'proveedor' => 'nullable|string|max:255',
            'fecha_adquisicion' => 'nullable|date',
            'fecha_garantia' => 'nullable|date',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_equipo_id' => 'tipo de equipo',
            'serie' => 'número de serie',
            'detalle' => 'detalles adicionales',
            'ram' => 'memoria RAM',
            'stock_actual' => 'cantidad en stock',
            'estado_uso' => 'estado de uso',
            'sede_id' => 'sede',
            'pertenece_sede' => 'pertenece a la sede',
            'tipo_adquisicion' => 'tipo de adquisición',
            'factura' => 'número de factura',
            'contrato' => 'número de contrato',
            'fecha_adquisicion' => 'fecha de adquisición',
            'fecha_garantia' => 'fecha de garantía',
        ];
    }
}
