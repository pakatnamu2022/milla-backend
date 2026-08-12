<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktActivityRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'budget_id'     => 'nullable|integer|exists:ap_mkt_budgets,id',
      'activity_type' => 'nullable|string|max:100',
      'status'        => 'nullable|string|in:planned,in_progress,executed,cancelled',
      'supplier_id'   => 'nullable|integer|exists:business_partners,id',
      'currency_id'   => 'nullable|integer|exists:type_currency,id',
      'search'        => 'nullable|string|max:200',
    ]);
  }
}
