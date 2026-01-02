<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\StoreRequest;

class ResetPasswordByCompanyRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'company_id' => 'required|integer|exists:companies,id',
    ];
  }
}
