<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApEngineTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_tipo_motores_vehiculo', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('engineType'))
        ,
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_tipo_motores_vehiculo', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('engineType')),
      ]
    ];
  }
}
