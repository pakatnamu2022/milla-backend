<?php

namespace App\Http\Requests\tp\configuracionComercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:250|unique:op_tipo_vehiculo,descripcion',
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción del tipo de vehículo es requerida',
            'descripcion.string' => 'La descripción debe ser un texto válido',
            'descripcion.max' => 'La descripción no puede exceder los 250 caracteres',
            'descripcion.unique' => 'Ya existe un tipo de vehículo con esta descripción',
        ];
    }
}