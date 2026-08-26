<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class RejectPurchaseRequestQuoteAdjustmentRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'reason' => ['nullable', 'string', 'max:1000'],
    ];
  }
}
