<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApBankRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => 'required|string|max:50|unique:ap_bank,code',
      'account_number' => 'nullable|string|max:50|unique:ap_bank,account_number',
      'cci' => 'nullable|string|max:50|unique:ap_bank,cci',
      'bank_id' => 'required|integer|exists:ap_commercial_masters,id',
      'currency_id' => 'required|integer|exists:type_currency,id',
      'sede_id' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El campo código debe ser una cadena de texto.',
      'code.max' => 'El campo código no debe exceder los 50 caracteres.',
      'code.unique' => 'El código ya está en uso.',

      'account_number.string' => 'El campo número de cuenta debe ser una cadena de texto.',
      'account_number.max' => 'El campo número de cuenta no debe exceder los 50 caracteres.',
      'account_number.unique' => 'El número de cuenta ya está en uso.',

      'cci.string' => 'El campo CCI debe ser una cadena de texto.',
      'cci.max' => 'El campo CCI no debe exceder los 50 caracteres.',
      'cci.unique' => 'El CCI ya está en uso.',

      'bank_id.required' => 'El campo banco es obligatorio.',
      'bank_id.integer' => 'El campo banco debe ser un número entero.',
      'bank_id.exists' => 'El banco seleccionado no es válido.',

      'currency_id.required' => 'El campo moneda es obligatorio.',
      'currency_id.integer' => 'El campo moneda debe ser un número entero.',
      'currency_id.exists' => 'La moneda seleccionada no es válida.',

//      'company_branch_id.required' => 'El campo sede es obligatorio.',
//      'company_branch_id.integer' => 'El campo sede debe ser un número entero.',
//      'company_branch_id.exists' => 'La sede seleccionada no es válida.',

      'sede_id.required' => 'El campo sede es obligatorio.',
      'sede_id.integer' => 'El campo sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no es válida.',
    ];
  }
}
