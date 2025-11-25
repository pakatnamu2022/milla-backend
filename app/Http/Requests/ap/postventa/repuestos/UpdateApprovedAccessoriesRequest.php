<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApprovedAccessoriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'string',
        'max:20',
        Rule::unique('approved_accessories', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('ApprovedAccessory')),
      ],
      'type' => ['sometimes', Rule::in(['SERVICIO', 'REPUESTO'])],
      'description' => ['sometimes', 'string'],
      'price' => ['sometimes', 'numeric'],
      'status' => ['sometimes', 'boolean'],
      'type_currency_id' => ['sometimes', 'exists:type_currency,id'],
      'body_type_id' => ['sometimes', 'exists:ap_commercial_masters,id']
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no debe exceder los 20 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'type.in' => 'El tipo debe ser SERVICIO o REPUESTO.',

      'description.string' => 'La descripción debe ser una cadena de texto.',

      'exchange_rate.numeric' => 'La tasa de cambio debe ser un número.',

      'price.numeric' => 'El precio debe ser un número.',

      'status.boolean' => 'El estado debe ser verdadero o falso.',

      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'body_type_id.exists' => 'El tipo de carrocería seleccionado no es válido.'
    ];
  }
}
