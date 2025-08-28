<?php

namespace App\Http\Requests\TypeCurrency;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreTypeCurrencyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'required',
        'string',
        'max:3',
        Rule::unique('type_currency', 'codigo')->whereNull('deleted_at'),
      ],
      'nombre' => [
        'required',
        'string',
        'max:50',
        Rule::unique('type_currency', 'nombre')->whereNull('deleted_at'),
      ],
      'simbolo' => [
        'required',
        'string',
        'max:5',
        Rule::unique('type_currency', 'simbolo')->whereNull('deleted_at'),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.required' => 'El código de la moneda es obligatorio.',
      'codigo.string' => 'El código de la moneda debe ser un texto.',
      'codigo.max' => 'El código de la moneda no puede tener más de 3 caracteres.',
      'codigo.unique' => 'El código de la moneda ya existe en el sistema.',

      'nombre.required' => 'El nombre de la moneda es obligatorio.',
      'nombre.string' => 'El nombre de la moneda debe ser un texto.',
      'nombre.max' => 'El nombre de la moneda no puede exceder los 50 caracteres.',
      'nombre.unique' => 'El nombre de la moneda ya está registrado.',

      'simbolo.required' => 'El símbolo de la moneda es obligatorio.',
      'simbolo.string' => 'El símbolo de la moneda debe ser un texto.',
      'simbolo.max' => 'El símbolo de la moneda no puede tener más de 5 caracteres.',
      'simbolo.unique' => 'El símbolo de la moneda ya está en uso.',
    ];
  }
}
