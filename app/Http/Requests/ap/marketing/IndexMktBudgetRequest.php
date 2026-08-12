<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktBudgetRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'plan_id'      => 'nullable|integer|exists:ap_mkt_plans,id',
      'type'         => 'nullable|string|in:regular,additional',
      'status'       => 'nullable|string|in:draft,approved,closed',
      'period_month' => 'nullable|integer|min:1|max:12',
      'currency_id'  => 'nullable|integer|exists:type_currency,id',
    ]);
  }
}
