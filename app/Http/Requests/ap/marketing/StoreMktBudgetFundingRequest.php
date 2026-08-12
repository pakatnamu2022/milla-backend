<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktBudgetFundingRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'source'      => 'required|string|in:AP,brand',
      'currency_id' => 'required|integer|exists:type_currency,id',
      'amount'      => 'required|numeric|min:0',
      'notes'       => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'source'      => 'fuente',
      'currency_id' => 'moneda',
      'amount'      => 'monto',
      'notes'       => 'notas',
    ];
  }
}
