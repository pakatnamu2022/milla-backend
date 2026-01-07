<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use App\Models\ap\comercial\PotentialBuyers;
use Illuminate\Validation\Rule;

class IndexPotentialBuyersRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'registration_date' => 'nullable|array|size:2',
      'registration_date.*' => 'nullable|date|date_format:Y-m-d',
      'worker_id' => 'nullable|exists:rrhh_persona,id',
      'sede_id' => 'nullable|exists:config_sede,id',
      'type' => 'nullable|in:LEADS,VISITA',
      'per_page' => 'nullable|integer|min:1|max:100',
      'all' => 'nullable|in:true,false',
      'page' => 'nullable|integer|min:1',
      'sort' => [
        'nullable',
        'string',
        Rule::in(PotentialBuyers::sorts)
      ],
      'created_at' => 'nullable|array|size:2',
      'created_at.*' => 'nullable|date|date_format:Y-m-d',
    ];
  }
}
