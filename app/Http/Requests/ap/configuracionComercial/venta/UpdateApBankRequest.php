<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApBankRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'codigo')
          ->whereNull('deleted_at')
          ->ignore($this->route('bank')),
      ],
      'numero_cuenta' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'numero_cuenta')
          ->whereNull('deleted_at')
          ->ignore($this->route('bank')),
      ],
      'cci' => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_bank', 'cci')
          ->whereNull('deleted_at')
          ->ignore($this->route('bank')),
      ],
      'banco_id' => 'nullable|integer|exists:ap_commercial_masters,id',
      'moneda_id' => 'nullable|integer|exists:type_currency,id',
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'status' => 'nullable|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.string' => 'El campo código debe ser una cadena de texto.',
      'codigo.max' => 'El campo código no debe exceder los 50 caracteres.',
      'codigo.unique' => 'El código ya está en uso.',

      'numero_cuenta.string' => 'El campo número de cuenta debe ser una cadena de texto.',
      'numero_cuenta.max' => 'El campo número de cuenta no debe exceder los 50 caracteres.',
      'numero_cuenta.unique' => 'El número de cuenta ya está en uso.',

      'cci.string' => 'El campo CCI debe ser una cadena de texto.',
      'cci.max' => 'El campo CCI no debe exceder los 50 caracteres.',
      'cci.unique' => 'El CCI ya está en uso.',

      'banco_id.integer' => 'El campo banco debe ser un número entero.',
      'banco_id.exists' => 'El banco seleccionado no es válido.',

      'moneda_id.integer' => 'El campo moneda debe ser un número entero.',
      'moneda_id.exists' => 'La moneda seleccionada no es válida.',

      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',

      'status.boolean' => 'El campo estado debe ser verdadero o falso.',
    ];
  }
}
