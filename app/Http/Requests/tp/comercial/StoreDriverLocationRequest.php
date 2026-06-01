<?php

namespace App\Http\Requests\tp\comercial;

use App\Http\Requests\StoreRequest;

class StoreDriverLocationRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'battery_level' => 'nullable|numeric|min:0|max:100',
            'timestamp' => 'nullable|date'
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'El identificador del dispositivo es obligatorio',
            'latitude.required' => 'La latitud es obligatoria',
            'longitude.required' => 'La longitud es obligatoria',
            'latitude.between' => 'Latitud inválida',
            'longitude.between' => 'Longitud inválida'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'timestamp' => $this->timestamp ?? now(),
        ]);
    }
}