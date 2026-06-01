<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverLocationConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:100|unique:driver_location_configuration,key',
            'value' => 'required',
            'description' => 'nullable|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'La clave de configuración es obligatoria',
            'key.unique' => 'Ya existe una configuración con esta clave',
            'value.required' => 'El valor es obligatorio'
        ];
    }
}