<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use Illuminate\Validation\Rule;

class MyOpportunityRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => 'nullable|integer|exists:rrhh_persona,id',
      'has_purchase_request_quote' => 'nullable|boolean|in:0,1',
      'opportunity_id' => [
        'nullable',
        'integer',
        Rule::exists('ap_opportunity', 'id')->whereNull('deleted_at'),
      ]
    ];
  }

  public function attributes()
  {
    return [
      'worker_id' => 'trabajador',
      'has_purchase_request_quote' => 'tiene cotización',
    ];
  }
}
