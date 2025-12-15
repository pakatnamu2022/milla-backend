<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexPotentialBuyersRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'registration_date' => 'nullable|array|size:2',
      'registration_date.*' => 'required|date|date_format:Y-m-d',
      'worker_id' => 'nullable|exists:rrhh_persona,id',
      'sede_id' => 'nullable|exists:config_sede,id',
      'type' => 'nullable|in:LEADS,VISITA',
      'per_page' => 'nullable|integer|min:1|max:100',
      'all' => 'nullable|in:true,false',
      'page' => 'nullable|integer|min:1',
      'sort' => 'nullable|string|in:id,registration_date,full_name,num_doc,sede_id,vehicle_brand_id',
    ];
  }
}
