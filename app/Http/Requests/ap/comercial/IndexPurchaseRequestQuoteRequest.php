<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexPurchaseRequestQuoteRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'has_vehicle' => 'nullable|boolean',
    ];
  }
}
