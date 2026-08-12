<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktProposalRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'activity_id' => 'nullable|integer|exists:mkt_activities,id',
      'supplier_id' => 'nullable|integer|exists:business_partners,id',
      'status'      => 'nullable|string|in:pending,approved,rejected',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
    ]);
  }
}
