<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class ChangePurchaseRequestQuoteSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id' => ['required', 'integer', 'exists:config_sede,id'],
    ];
  }

  public function attributes(): array
  {
    return [
      'sede_id' => 'Sede',
    ];
  }
}
