<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelControlRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'conductor_id' => 'required|exists:rrhh_persona,id',
            'tracto_id' => 'required|exists:op_vehiculo,id',
            'carreta_id' => 'nullable|exists:op_vehiculo,id',
            'idcliente' => 'required|exists:rrhh_persona,id',
            'fecha_viaje' => 'required|date',
            'observacion_comercial' => 'nullable|string|max:500',
            'proxima_prog' => 'nullable|date',
        ];
    }
}