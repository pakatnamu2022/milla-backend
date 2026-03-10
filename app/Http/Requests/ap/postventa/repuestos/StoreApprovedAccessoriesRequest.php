<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Validation\Rule;

class StoreApprovedAccessoriesRequest extends StoreRequest
{
  public function prepareForValidation(): void
  {
    $typeOperationId = (int) $this->input('type_operation_id');
    $currencyId = match ($typeOperationId) {
      ApMasters::TIPO_OPERACION_COMERCIAL => TypeCurrency::USD_ID,
      ApMasters::TIPO_OPERACION_POSTVENTA => TypeCurrency::PEN_ID,
      default                             => TypeCurrency::PEN_ID,
    };
    $this->merge(['type_currency_id' => $currencyId]);
  }

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
      'type_operation_id' => [
        'required', 'integer',
        Rule::in([ApMasters::TIPO_OPERACION_COMERCIAL, ApMasters::TIPO_OPERACION_POSTVENTA]),
        'exists:ap_masters,id',
      ],
      'description' => ['required', 'string'],
      'price' => ['required', 'numeric'],
      'type_currency_id' => ['required', 'exists:type_currency,id'],
      'body_type_id' => ['required', 'exists:ap_masters,id']
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código es obligatorio.',
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no debe exceder los 20 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.in' => 'El tipo de operación debe ser Comercial o Posventa.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'description.required' => 'La descripción es obligatoria.',
      'description.string' => 'La descripción debe ser una cadena de texto.',

      'price.required' => 'El precio es obligatorio.',
      'price.numeric' => 'El precio debe ser un número.',

      'type_currency_id.required' => 'El tipo de moneda es obligatorio.',
      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'body_type_id.required' => 'El tipo de carrocería es obligatorio.',
      'body_type_id.exists' => 'El tipo de carrocería seleccionado no es válido.'
    ];
  }
}
