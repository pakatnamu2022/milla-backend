<?php

namespace App\Http\Requests\ap\configuracionComercial\vehiculo;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApBrandGroupsRequest extends StoreRequest
{
    public function rules(): array
    {
      return [
        'name' => [
          'nullable',
          'string',
          'max:250',
          Rule::unique('ap_grupo_marca', 'name')
            ->whereNull('deleted_at')
            ->ignore($this->route('brandGroups')),
        ]
      ];
    }
}
