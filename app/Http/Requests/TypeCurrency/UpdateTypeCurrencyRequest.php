<?php

namespace App\Http\Requests\TypeCurrency;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateTypeCurrencyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:3',
        Rule::unique('type_currency', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
      'nombre' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('type_currency', 'nombre')
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
      'simbolo' => [
        'nullable',
        'string',
        'max:5',
        Rule::unique('type_currency', 'simbolo')
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.string' => 'El código de la moneda debe ser un texto.',
      'codigo.max' => 'El código de la moneda no puede tener más de 3 caracteres.',
      'codigo.unique' => 'El código de la moneda ya existe en el sistema.',

      'nombre.string' => 'El nombre de la moneda debe ser un texto.',
      'nombre.max' => 'El nombre de la moneda no puede exceder los 50 caracteres.',
      'nombre.unique' => 'El nombre de la moneda ya está registrado.',

      'simbolo.string' => 'El símbolo de la moneda debe ser un texto.',
      'simbolo.max' => 'El símbolo de la moneda no puede tener más de 5 caracteres.',
      'simbolo.unique' => 'El símbolo de la moneda ya está en uso.',
    ];
  }
}
