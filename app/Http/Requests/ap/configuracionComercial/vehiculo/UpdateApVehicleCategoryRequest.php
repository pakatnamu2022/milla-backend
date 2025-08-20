<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApVehicleCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
          'name' => [
            'nullable',
            'string',
            'max:100',
            Rule::unique('ap_categoria_vehiculos', 'name')
              ->whereNull('deleted_at')
              ->ignore($this->route('vehicleCategory')),
          ]
        ];
    }
}
