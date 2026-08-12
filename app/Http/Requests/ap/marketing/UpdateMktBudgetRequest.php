<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class UpdateMktBudgetRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plan_id'          => 'nullable|integer|exists:ap_mkt_plans,id',
      'type'             => 'nullable|string|in:regular,additional',
      'period_month'     => 'nullable|integer|min:1|max:12',
      'currency_id'      => 'nullable|integer|exists:type_currency,id',
      'amount_estimated' => 'nullable|numeric|min:0',
      'amount_executed'  => 'nullable|numeric|min:0',
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
      'amount_executed'  => 'monto ejecutado',
      'status'           => 'estado',
      'notes'            => 'notas',
    ];
  }
}
