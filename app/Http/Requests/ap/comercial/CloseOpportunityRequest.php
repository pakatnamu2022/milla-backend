<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class CloseOpportunityRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'comment' => 'required|string',
    ];
  }

  public function attributes()
  {
    return [
      'comment' => 'comentario',
    ];
  }
}
