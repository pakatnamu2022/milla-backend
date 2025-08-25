<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApBrandGroupsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'nullable',
        'string',
        'max:250',
        Rule::unique('ap_grupo_marca', 'name')
          ->whereNull('deleted_at')
          ->ignore($this->route('brandGroup')),
      ],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 250 caracteres.',
      'name.unique' => 'El nombre ya está en uso.',
      'status.boolean' => 'El estado debe ser verdadero o falso.',
    ];
  }
}
