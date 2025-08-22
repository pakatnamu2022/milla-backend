<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApProductTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:100',
        Rule::unique('ap_tipo_productos_vehiculo', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('productType')),
      ],
      'descripcion' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('ap_tipo_productos_vehiculo', 'descripcion')
          ->whereNull('deleted_at')
          ->ignore($this->route('productType')),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'name.string' => 'El campo nombre debe ser una cadena de texto.',
      'name.max' => 'El campo nombre no debe exceder los 100 caracteres.',
      'name.unique' => 'El campo nombre ya existe.',

      'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
      'descripcion.max' => 'El campo descripción no debe exceder los 255 caracteres.',
      'descripcion.unique' => 'El campo descripción ya existe.',
    ];
  }
}
