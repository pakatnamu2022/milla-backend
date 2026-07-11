<?php

namespace App\Http\Requests\tp\configuracionComercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_vehiculo_id' => 'required|exists:op_tipo_vehiculo,id',
            'placa' => [
                'required',
                'string',
                'max:20',
                Rule::unique('op_vehiculo', 'placa')
            ],
            'modelo' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'serie_chasis' => 'nullable|string|max:100',
            'motor' => 'nullable|string|max:100',
            'num_mtc' => 'nullable|string|max:50',
            'tarjeta_circulacion' => 'nullable|string|max:50',
            'kilometraje' => 'nullable|numeric|min:0',
            'tercero' => 'nullable|boolean',
            'capacidad' => 'nullable|numeric|min:0',
            'capacidad_bruta' => 'nullable|numeric|min:0',
            'reserva' => 'nullable|numeric|min:0',
            'capacidad_util' => 'nullable|numeric|min:0',
            'vehiculo_status' => 'nullable|in:0,1',
            'status_geotab_km' => 'nullable|in:0,1',
            'status_matpel' => 'nullable|in:0,1',
            'status_ubicacion' => 'nullable|in:0,1',
            'sede_id' => 'required|exists:sede,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_vehiculo_id.required' => 'El tipo de vehículo es requerido',
            'tipo_vehiculo_id.exists' => 'El tipo de vehículo seleccionado no existe',
            'placa.required' => 'La placa es requerida',
            'placa.string' => 'La placa debe ser un texto válido',
            'placa.max' => 'La placa no puede exceder los 20 caracteres',
            'placa.unique' => 'Ya existe un vehículo con esta placa',
            'kilometraje.numeric' => 'El kilometraje debe ser un número',
            'kilometraje.min' => 'El kilometraje debe ser mayor o igual a 0',
            'capacidad.numeric' => 'La capacidad debe ser un número',
            'capacidad.min' => 'La capacidad debe ser mayor o igual a 0',
            'capacidad_bruta.numeric' => 'La capacidad bruta debe ser un número',
            'capacidad_bruta.min' => 'La capacidad bruta debe ser mayor o igual a 0',
            'reserva.numeric' => 'La reserva debe ser un número',
            'reserva.min' => 'La reserva debe ser mayor o igual a 0',
            'capacidad_util.numeric' => 'La capacidad útil debe ser un número',
            'capacidad_util.min' => 'La capacidad útil debe ser mayor o igual a 0',
            'sede_id.required' => 'La sede es requerida',
            'sede_id.exists' => 'La sede seleccionada no existe',
        ];
    }
}