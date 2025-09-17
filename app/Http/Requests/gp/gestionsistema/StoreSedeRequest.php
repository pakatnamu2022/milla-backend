<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreSedeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'abreviatura' => [
        'required',
        'string',
        'max:10',
        Rule::unique('warehouse', 'dyn_code')
          ->whereNull('deleted_at'),
      ],
    ];
  }
}
