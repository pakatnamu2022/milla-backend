<?php

namespace App\Http\Requests\ap;

use App\Http\Requests\IndexRequest;

class IndexApMastersRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'type' => ['nullable'],
      'type.*' => ['string'], // Valida cada elemento si es array
    ];
  }
}
