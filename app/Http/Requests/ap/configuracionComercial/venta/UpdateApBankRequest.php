<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApBankRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('bankAp')),
      ],
      'account_number' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'account_number')
          ->whereNull('deleted_at')
          ->ignore($this->route('bankAp')),
      ],
      'cci' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'cci')
          ->whereNull('deleted_at')
          ->ignore($this->route('bankAp')),
      ],
      'bank_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
      //'company_branch_id' => 'nullable|integer|exists:company_branch,id',
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'status' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 50 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'account_number.string' => 'El campo número de cuenta debe ser una cadena de texto.',
      'account_number.max' => 'El campo número de cuenta no debe exceder los 50 caracteres.',
      'account_number.unique' => 'El número de cuenta ya está en uso.',

      'cci.string' => 'El campo CCI debe ser una cadena de texto.',
      'cci.max' => 'El campo CCI no debe exceder los 50 caracteres.',
      'cci.unique' => 'El CCI ya está en uso.',

      'bank_id.integer' => 'El campo banco debe ser un número entero.',
      'bank_id.exists' => 'El banco seleccionado no es válido.',

      'currency_id.integer' => 'El campo moneda debe ser un número entero.',
      'currency_id.exists' => 'La moneda seleccionada no es válida.',

      'company_branch_id.integer' => 'El campo sede debe ser un número entero.',
      'company_branch_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
