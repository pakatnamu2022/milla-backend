<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexShippingGuidesRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'issue_date' => 'nullable|array|min:2|max:2',
      'issue_date.*' => 'nullable|date',
    ];
  }
}
