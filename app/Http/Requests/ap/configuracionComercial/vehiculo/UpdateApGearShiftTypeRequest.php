<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApGearShiftTypeRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'codigo' => [
                'nullable',
                'string',
                'max:100',
              Rule::unique('ap_tipo_cambio_marcha', 'codigo')
                ->whereNull('deleted_at')
                ->ignore($this->route('gearShiftType')),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
              Rule::unique('ap_tipo_cambio_marcha', 'descripcion')
                ->whereNull('deleted_at')
                ->ignore($this->route('gearShiftType')),
            ],
        ];
    }

    public function messages(): array {
        return [
            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.string' => 'El campo código debe ser una cadena de texto.',
            'codigo.max' => 'El campo código no debe exceder los 100 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',

            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
            'descripcion.unique' => 'La descripción ya está en uso.',
        ];
    }
}
