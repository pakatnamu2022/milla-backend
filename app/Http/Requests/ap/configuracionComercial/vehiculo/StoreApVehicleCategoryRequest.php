<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApVehicleCategoryRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
          'name' => [
            'required',
            'string',
            'max:100',
            Rule::unique('ap_categoria_vehiculos', 'name')->whereNull('deleted_at'),],
        ];
    }
}
