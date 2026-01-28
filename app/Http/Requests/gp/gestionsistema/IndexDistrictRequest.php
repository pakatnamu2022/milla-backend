<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\IndexRequest;

class IndexDistrictRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'all' => 'nullable|string|in:true,false',
      'has_sede' => 'nullable|integer:|in:1,0',
    ];
  }
}
