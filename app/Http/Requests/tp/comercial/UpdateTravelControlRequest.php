<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelControlRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'conductor_id' => 'sometimes|exists:rrhh_persona,id',
            'tracto_id' => 'sometimes|exists:op_vehiculo,id',
            'carreta_id' => 'nullable|exists:op_vehiculo,id',
            'idcliente' => 'sometimes|exists:rrhh_persona,id',
            'observacion_comercial' => 'nullable|string|max:500',
            'proxima_prog' => 'nullable|date',
        ];
    }
}