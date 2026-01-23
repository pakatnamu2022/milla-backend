<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreTypeCurrencyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:3',
        Rule::unique('type_currency', 'code')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at'),
      ],
      'name' => [
        'required',
        'string',
        'max:50',
        Rule::unique('type_currency', 'name')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at'),
      ],
      'symbol' => [
        'required',
        'string',
        'max:5',
        Rule::unique('type_currency', 'symbol')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at'),
      ],
      'area_id' => [
        'required',
        'exists:ap_masters,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código de la moneda es obligatorio.',
      'code.string' => 'El código de la moneda debe ser un texto.',
      'code.max' => 'El código de la moneda no puede tener más de 3 caracteres.',
      'code.unique' => 'El código de la moneda ya existe en el sistema para el área seleccionada.',

      'name.required' => 'El nombre de la moneda es obligatorio.',
      'name.string' => 'El nombre de la moneda debe ser un texto.',
      'name.max' => 'El nombre de la moneda no puede exceder los 50 caracteres.',
      'name.unique' => 'El nombre de la moneda ya está registrado para el área seleccionada.',

      'symbol.required' => 'El símbolo de la moneda es obligatorio.',
      'symbol.string' => 'El símbolo de la moneda debe ser un texto.',
      'symbol.max' => 'El símbolo de la moneda no puede tener más de 5 caracteres.',
      'symbol.unique' => 'El símbolo de la moneda ya está en uso para el área seleccionada.',

      'area_id.required' => 'El área es obligatoria.',
      'area_id.exists' => 'El área seleccionada no es válida.',
    ];
  }
}
