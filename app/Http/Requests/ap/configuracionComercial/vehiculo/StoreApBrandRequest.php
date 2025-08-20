<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApBrandRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
          'codigo' => [
            'required',
            'string',
            'max:100',
            Rule::unique('ap_marca_vehiculo', 'codigo')->whereNull('deleted_at'),
          ],
          'codigo_dyn' => [
            'required',
            'string',
            'max:100',
            Rule::unique('ap_marca_vehiculo', 'codigo_dyn')->whereNull('deleted_at'),
          ],
          'name' => [
            'required',
            'string',
            'max:150',
            Rule::unique('ap_marca_vehiculo', 'name')->whereNull('deleted_at'),
          ],
          'descripcion' => [
            'required',
            'string',
            'max:250',
            Rule::unique('ap_marca_vehiculo', 'descripcion')->whereNull('deleted_at'),
          ],
          'logo' => [
            'nullable',
            'string',
            'max:250',
          ],
          'logo_min' => [
            'nullable',
            'string',
            'max:250',
          ],
          'Responsable' => [
            'nullable',
            'string',
            'max:1000',
          ],
        ];
    }
}
