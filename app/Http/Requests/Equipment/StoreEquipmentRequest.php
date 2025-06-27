<?php

namespace App\Http\Requests\Equipment;

use App\Http\Requests\StoreRequest;

class StoreEquipmentRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'equipo' => 'required|string|max:255',
            'tipo_equipo_id' => 'required|integer|exists:help_tipo_equipo,id',
            'marca_modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'detalle' => 'nullable|string|max:500',
            'ram' => 'nullable|string|max:50',
            'almacenamiento' => 'nullable|string|max:50',
            'procesador' => 'nullable|string|max:100',
            'stock_actual' => 'required|integer|min:0',
            'estado_uso' => 'required|in:NUEVO,USADO',
            'sede_id' => 'required|integer|exists:config_sede,id',
            'pertenece_sede' => 'boolean',
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
            'equipo' => 'nombre del equipo',
            'tipo_equipo_id' => 'tipo de equipo',
            'sede_id' => 'sede',
            'stock_actual' => 'cantidad en stock',
            'estado_uso' => 'estado de uso',
            'marca_modelo' => 'marca y modelo',
            'serie' => 'número de serie',
            'detalle' => 'detalles adicionales',
            'ram' => 'memoria RAM',
            'almacenamiento' => 'almacenamiento',
            'procesador' => 'procesador',
            'pertenece_sede' => 'pertenece a la sede',
            'tipo_adquisicion' => 'tipo de adquisición',
            'factura' => 'número de factura',
            'contrato' => 'número de contrato',
            'proveedor' => 'proveedor',
            'fecha_adquisicion' => 'fecha de adquisición',
            'fecha_garantia' => 'fecha de garantía',
        ];
    }
}
