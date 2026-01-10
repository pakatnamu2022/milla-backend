<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;

class StoreApBankRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => 'required|string|max:50|unique:ap_bank,code',
      'description' => 'nullable|string',
      'account_number' => 'nullable|string|max:50|unique:ap_bank,account_number',
      'cci' => 'nullable|string|max:50|unique:ap_bank,cci',
      'bank_id' => 'required|integer|exists:ap_masters,id',
      'currency_id' => 'required|integer|exists:type_currency,id',
      'sede_id' => 'required|integer|exists:config_sede,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'code' => 'código',
      'description' => 'descripción',
      'account_number' => 'número de cuenta',
      'cci' => 'CCI',
      'bank_id' => 'banco',
      'currency_id' => 'moneda',
      'sede_id' => 'sede',
    ];
  }
}
