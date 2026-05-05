<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Validation\Rule;

class UpdateApprovedAccessoriesRequest extends StoreRequest
{
  public function prepareForValidation(): void
  {
    if ($this->has('type_operation_id')) {
      $typeOperationId = (int) $this->input('type_operation_id');
      $currencyId = match ($typeOperationId) {
        ApMasters::TIPO_OPERACION_COMERCIAL => TypeCurrency::USD_ID,
        ApMasters::TIPO_OPERACION_POSTVENTA => TypeCurrency::PEN_ID,
        default                             => TypeCurrency::PEN_ID,
      };
      $this->merge(['type_currency_id' => $currencyId]);
    }
  }

  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'string',
        'max:20',
        Rule::unique('approved_accessories', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('approvedAccessory')),
      ],
      'type_operation_id' => [
        'sometimes', 'integer',
        Rule::in([ApMasters::TIPO_OPERACION_COMERCIAL, ApMasters::TIPO_OPERACION_POSTVENTA]),
        'exists:ap_masters,id',
      ],
      'description' => ['sometimes', 'string'],
      'price' => ['sometimes', 'numeric'],
      'status' => ['sometimes', 'boolean'],
      'type_currency_id' => ['sometimes', 'exists:type_currency,id'],
      'body_type_id' => ['sometimes', 'exists:ap_masters,id']
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.max' => 'El código no debe exceder los 20 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'type_operation_id.integer' => 'El tipo de operación debe ser un número entero.',
      'type_operation_id.in' => 'El tipo de operación debe ser Comercial o Posventa.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'description.string' => 'La descripción debe ser una cadena de texto.',

      'price.numeric' => 'El precio debe ser un número.',

      'status.boolean' => 'El estado debe ser verdadero o falso.',

      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'body_type_id.exists' => 'El tipo de carrocería seleccionado no es válido.'
    ];
  }
}
