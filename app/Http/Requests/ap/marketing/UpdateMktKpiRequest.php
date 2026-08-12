<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class UpdateMktKpiRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'activity_id'  => 'nullable|integer|exists:mkt_activities,id',
      'period_month' => 'nullable|integer|min:1|max:12',
      'period_year'  => 'nullable|integer|min:2020|max:2100',
      'leads'        => 'nullable|integer|min:0',
      'sales'        => 'nullable|integer|min:0',
      'investment'   => 'nullable|numeric|min:0',
      'currency_id'  => 'nullable|integer|exists:type_currency,id',
      'notes'        => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'activity_id'  => 'actividad',
      'period_month' => 'mes',
      'period_year'  => 'año',
      'leads'        => 'leads',
      'sales'        => 'ventas',
      'investment'   => 'inversión',
      'currency_id'  => 'moneda',
      'notes'        => 'notas',
    ];
  }
}
