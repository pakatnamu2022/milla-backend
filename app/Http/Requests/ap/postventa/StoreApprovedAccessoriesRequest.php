<?php

namespace App\Http\Requests\ap\postventa;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApprovedAccessoriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:20',
        Rule::unique('approved_accessories', 'code')
          ->whereNull('deleted_at'),
      ],
      'type' => ['required', Rule::in(['SERVICIO', 'REPUESTO'])],
      'description' => ['required', 'string'],
      'exchange_rate' => ['nullable', 'numeric'],
      'price' => ['required', 'numeric'],
      'type_currency_id' => ['required', 'exists:type_currency,id'],
      'body_type_id' => ['required', 'exists:ap_commercial_masters,id']
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código es obligatorio.',
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no debe exceder los 20 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'type.required' => 'El tipo es obligatorio.',
      'type.in' => 'El tipo debe ser SERVICIO o REPUESTO.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',

      'exchange_rate.numeric' => 'La tasa de cambio debe ser un número.',

      'price.required' => 'El precio es obligatorio.',
      'price.numeric' => 'El precio debe ser un número.',

      'type_currency_id.required' => 'El tipo de moneda es obligatorio.',
      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'body_type_id.required' => 'El tipo de carrocería es obligatorio.',
      'body_type_id.exists' => 'El tipo de carrocería seleccionado no es válido.'
    ];
  }
}
