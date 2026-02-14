<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class StoreTelephoneAccountRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => 'required|exists:companies,id',
      'account_number' => 'required|string|max:255',
      'operator' => 'required|string|max:255',
    ];
  }
}
