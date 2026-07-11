<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('control_vehicle_type') ?? $this->id;

        return [
            'descripcion' => [
                'required',
                'string',
                'max:250',
                Rule::unique('op_tipo_vehiculo', 'descripcion')->ignore($id)
            ],
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