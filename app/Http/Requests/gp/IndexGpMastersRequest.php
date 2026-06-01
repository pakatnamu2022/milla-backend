<?php

namespace App\Http\Requests\gp;

use App\Http\Requests\IndexRequest;

class IndexGpMastersRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'type' => ['nullable'],
      'type.*' => ['string'], // Valida cada elemento si es array
    ];
  }
}