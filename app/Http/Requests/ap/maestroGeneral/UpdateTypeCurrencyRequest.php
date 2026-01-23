<?php

namespace App\Http\Requests\ap\maestroGeneral;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateTypeCurrencyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:3',
        Rule::unique('type_currency', 'code')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
      'name' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('type_currency', 'name')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
      'symbol' => [
        'nullable',
        'string',
        'max:5',
        Rule::unique('type_currency', 'symbol')
          ->where('area_id', $this->input('area_id'))
          ->whereNull('deleted_at')
          ->ignore($this->route('typeCurrency')),
      ],
      'area_id' => [
        'nullable',
        'exists:ap_masters,id',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El código de la moneda debe ser un texto.',
      'code.max' => 'El código de la moneda no puede tener más de 3 caracteres.',
      'code.unique' => 'El código de la moneda ya existe en el sistema para el área seleccionada.',

      'name.string' => 'El nombre de la moneda debe ser un texto.',
      'name.max' => 'El nombre de la moneda no puede exceder los 50 caracteres.',
      'name.unique' => 'El nombre de la moneda ya está registrado para el área seleccionada.',

      'symbol.string' => 'El símbolo de la moneda debe ser un texto.',
      'symbol.max' => 'El símbolo de la moneda no puede tener más de 5 caracteres.',
      'symbol.unique' => 'El símbolo de la moneda ya está en uso para el área seleccionada.',

      'area_id.exists' => 'El área seleccionada no es válida.',
    ];
  }
}
