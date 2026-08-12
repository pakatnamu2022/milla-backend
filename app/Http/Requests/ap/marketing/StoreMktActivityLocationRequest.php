<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktActivityLocationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id'       => 'nullable|integer|exists:config_sede,id',
      'location_name' => 'nullable|string|max:150',
      'currency_id'   => 'nullable|integer|exists:type_currency,id',
      'amount'        => 'nullable|numeric|min:0',
      'notes'         => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'sede_id'       => 'sede',
      'location_name' => 'nombre del lugar',
      'currency_id'   => 'moneda',
      'amount'        => 'monto',
      'notes'         => 'notas',
    ];
  }
}
