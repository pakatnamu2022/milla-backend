<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use App\Models\ap\comercial\Opportunity;
use Illuminate\Validation\Rule;

class IndexOpportunityRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'has_purchase_request_quote' => ['nullable', 'boolean', 'in:0,1'],
      'opportunity_status_id' => Rule::in(Opportunity::OPPORTUNITY_STATUS_ID),
      'lead_id' => 'nullable|integer|exists_soft:potential_buyers,id',
      'worker_id' => [
        'nullable',
        'integer',
        Rule::in(Opportunity::whereNull('deleted_at')->pluck('worker_id')->toArray())
      ]
    ];
  }
}
