<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class MyLeadsRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => 'nullable|integer|exists:rrhh_persona,id',
    ];
  }

  public function attributes()
  {
    return [
      'worker_id' => 'trabajador',
    ];
  }
}
