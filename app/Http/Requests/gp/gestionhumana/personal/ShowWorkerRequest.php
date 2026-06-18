<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Http\Requests\IndexRequest;

class ShowWorkerRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'showExtra' => 'nullable|boolean',
    ];
  }
}
