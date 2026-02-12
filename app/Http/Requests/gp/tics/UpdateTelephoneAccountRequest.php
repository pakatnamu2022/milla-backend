<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class UpdateTelephoneAccountRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => 'sometimes|exists:companies,id',
      'account_number' => 'sometimes|string|max:255',
      'operator' => 'sometimes|string|max:255',
    ];
  }
}
