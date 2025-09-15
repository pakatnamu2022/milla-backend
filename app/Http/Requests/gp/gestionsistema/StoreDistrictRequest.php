<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreDistrictRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:50',
        Rule::unique('district', 'name')
          ->whereNull('deleted_at'),
      ],
      'ubigeo' => [
        'required',
        'string',
        'max:6',
        Rule::unique('district', 'ubigeo')
          ->whereNull('deleted_at'),
      ],
      'province_id' => [
        'required',
        'integer',
        'exists:province,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'El campo nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 50 caracteres.',
      'name.unique' => 'El nombre ingresado ya existe en los registros.',

      'ubigeo.required' => 'El campo ubigeo es obligatorio.',
      'ubigeo.string' => 'El ubigeo debe ser una cadena de texto.',
      'ubigeo.max' => 'El ubigeo no debe exceder los 6 caracteres.',
      'ubigeo.unique' => 'El ubigeo ingresado ya existe en los registros.',

      'province_id.required' => 'El campo provincia es obligatorio.',
      'province_id.integer' => 'El campo provincia debe ser un número entero.',
      'province_id.exists' => 'La provincia seleccionada no es válida.',
    ];
  }
}
