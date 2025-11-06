<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexOpportunityRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'has_purchase_request_quote' => ['nullable', 'boolean', 'in:0,1'],
    ];
  }
}
