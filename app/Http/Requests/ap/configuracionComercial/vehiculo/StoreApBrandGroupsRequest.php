<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreApBrandGroupsRequest extends StoreRequest
{
    public function rules(): array
    {
      return [
        'name' => [
          'required',
          'string',
          'max:250',
          Rule::unique('ap_grupo_marca', 'name')->whereNull('deleted_at'),],
      ];
    }
}
