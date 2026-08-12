<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktBudgetRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plan_id'          => 'required|integer|exists:ap_mkt_plans,id',
      'type'             => 'required|string|in:regular,additional',
      'period_month'     => 'nullable|integer|min:1|max:12',
      'currency_id'      => 'required|integer|exists:type_currency,id',
      'amount_estimated' => 'required|numeric|min:0',
      'status'           => 'nullable|string|in:draft,approved,closed',
      'notes'            => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'plan_id'          => 'plan',
      'type'             => 'tipo',
      'period_month'     => 'mes',
      'currency_id'      => 'moneda',
      'amount_estimated' => 'monto estimado',
      'status'           => 'estado',
      'notes'            => 'notas',
    ];
  }
}
