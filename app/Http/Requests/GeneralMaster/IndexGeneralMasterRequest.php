<?php

namespace App\Http\Requests\GeneralMaster;

use App\Http\Requests\IndexRequest;

class IndexGeneralMasterRequest extends IndexRequest
{
  public function rules(): array
  {
    // Merge parent's rules so this request inherits base index rules.
    return [
      'code' => 'nullable|string',
      ...parent::rules()
    ];
  }
}
