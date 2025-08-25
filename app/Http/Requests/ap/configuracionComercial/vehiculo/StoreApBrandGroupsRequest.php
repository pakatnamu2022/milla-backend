<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApBrandGroupsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:250',
        Rule::unique('ap_grupo_marca', 'name')->whereNull('deleted_at'),],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 250 caracteres.',
      'name.unique' => 'El nombre ya está en uso.',
    ];
  }
}
