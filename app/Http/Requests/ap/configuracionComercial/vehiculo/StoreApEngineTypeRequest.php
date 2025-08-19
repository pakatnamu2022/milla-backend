<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApEngineTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'required',
        'string',
        'max:100',
        Rule::unique('ap_tipo_motores_vehiculo', 'codigo')->whereNull('deleted_at'),
      ],
      'descripcion' => [
        'required',
        'string',
        'max:255',
        Rule::unique('ap_tipo_motores_vehiculo', 'descripcion')->whereNull('deleted_at'),
      ]
    ];
  }
}
