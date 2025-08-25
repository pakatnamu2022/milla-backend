<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApBodyTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_tipo_carroceria', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('bodyType')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_tipo_carroceria', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('bodyType')),
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }
}
