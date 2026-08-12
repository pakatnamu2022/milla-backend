<?php

namespace App\Http\Requests\ap\compras;

use Illuminate\Foundation\Http\FormRequest;

class UnidadesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estatus'                  => 'nullable|string|max:100',
            'sede'                     => 'nullable|string|max:100',
            'renovaciones'             => 'nullable|integer|min:0|max:8',
            'dias_vencido_min'         => 'nullable|integer|min:0',
            'dias_vencido_max'         => 'nullable|integer|min:0|gte:dias_vencido_min',
            'fecha_emision_desde'      => 'nullable|date_format:Y-m-d',
            'fecha_emision_hasta'      => 'nullable|date_format:Y-m-d|after_or_equal:fecha_emision_desde',
            'fecha_vencimiento_desde'  => 'nullable|date_format:Y-m-d',
            'fecha_vencimiento_hasta'  => 'nullable|date_format:Y-m-d|after_or_equal:fecha_vencimiento_desde',
            'group_by'                 => 'nullable|string|in:estatus,sede,renovaciones,marca_vehiculo,modelo_vehiculo,tipo_vehiculo',
        ];
    }
}
