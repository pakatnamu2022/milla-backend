<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApBrandRequest extends StoreRequest
{
    public function rules(): array
    {
      return [
        'codigo' => [
          'nullable',
          'string',
          'max:100',
          Rule::unique('ap_marca_vehiculo', 'codigo')->whereNull('deleted_at')
            ->whereNull('deleted_at')
            ->ignore($this->route('brand')),
        ],
        'codigo_dyn' => [
          'nullable',
          'string',
          'max:100',
          Rule::unique('ap_marca_vehiculo', 'codigo_dyn')->whereNull('deleted_at')
            ->whereNull('deleted_at')
            ->ignore($this->route('brand')),
        ],
        'name' => [
          'nullable',
          'string',
          'max:150',
          Rule::unique('ap_marca_vehiculo', 'name')->whereNull('deleted_at')
            ->whereNull('deleted_at')
            ->ignore($this->route('brand')),
        ],
        'descripcion' => [
          'nullable',
          'string',
          'max:250',
          Rule::unique('ap_marca_vehiculo', 'descripcion')->whereNull('deleted_at')
            ->whereNull('deleted_at')
            ->ignore($this->route('brand')),
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
          'max:1000'
        ]
      ];
    }
}
