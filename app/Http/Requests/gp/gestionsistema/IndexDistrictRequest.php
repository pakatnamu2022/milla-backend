<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\IndexRequest;

class IndexDistrictRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'all' => 'string|in:true,false',
    ];
  }
}
