<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktKpiRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'activity_id'  => 'nullable|integer|exists:ap_mkt_activities,id',
      'period_month' => 'nullable|integer|min:1|max:12',
      'period_year'  => 'nullable|integer|min:2020|max:2100',
      'currency_id'  => 'nullable|integer|exists:type_currency,id',
    ]);
  }
}
