<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApBankRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'codigo' => 'required|string|max:50|unique:ap_bank,codigo',
      'numero_cuenta' => 'nullable|string|max:50|unique:ap_bank,numero_cuenta',
      'cci' => 'nullable|string|max:50|unique:ap_bank,cci',
      'banco_id' => 'required|integer|exists:ap_commercial_masters,id',
      'moneda_id' => 'required|integer|exists:type_currency,id',
      'sede_id' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'codigo.required' => 'El campo código es obligatorio.',
      'codigo.string' => 'El campo código debe ser una cadena de texto.',
      'codigo.max' => 'El campo código no debe exceder los 50 caracteres.',
      'codigo.unique' => 'El código ya está en uso.',

      'numero_cuenta.string' => 'El campo número de cuenta debe ser una cadena de texto.',
      'numero_cuenta.max' => 'El campo número de cuenta no debe exceder los 50 caracteres.',
      'numero_cuenta.unique' => 'El número de cuenta ya está en uso.',

      'cci.string' => 'El campo CCI debe ser una cadena de texto.',
      'cci.max' => 'El campo CCI no debe exceder los 50 caracteres.',
      'cci.unique' => 'El CCI ya está en uso.',

      'banco_id.required' => 'El campo banco es obligatorio.',
      'banco_id.integer' => 'El campo banco debe ser un número entero.',
      'banco_id.exists' => 'El banco seleccionado no es válido.',

      'moneda_id.required' => 'El campo moneda es obligatorio.',
      'moneda_id.integer' => 'El campo moneda debe ser un número entero.',
      'moneda_id.exists' => 'La moneda seleccionada no es válida.',

      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
