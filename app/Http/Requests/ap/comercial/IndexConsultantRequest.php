<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexConsultantRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'month' => ['required', 'integer'],
      'year' => ['required', 'integer'],
    ];
  }
}
