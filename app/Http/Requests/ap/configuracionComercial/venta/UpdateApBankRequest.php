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
      'description' => 'nullable|string',
      'bank_id' => 'nullable|integer|exists:ap_masters,id',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'status' => 'nullable|boolean',
    ];
  }

  public function attributes()
  {
    return [
      'code' => 'código',
      'description' => 'descripción',
      'account_number' => 'número de cuenta',
      'cci' => 'CCI',
      'bank_id' => 'banco',
      'currency_id' => 'moneda',
      'sede_id' => 'sede',
      'status' => 'estado',
    ];
  }
}
