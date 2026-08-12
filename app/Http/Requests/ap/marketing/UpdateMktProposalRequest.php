<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class UpdateMktProposalRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'activity_id' => 'nullable|integer|exists:mkt_activities,id',
      'supplier_id' => 'nullable|integer|exists:business_partners,id',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
      'amount'      => 'nullable|numeric|min:0',
      'description' => 'nullable|string',
      'status'      => 'nullable|string|in:pending,approved,rejected',
      'notes'       => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'activity_id' => 'actividad',
      'supplier_id' => 'proveedor',
      'currency_id' => 'moneda',
      'amount'      => 'monto',
      'description' => 'descripción',
      'status'      => 'estado',
      'notes'       => 'notas',
    ];
  }
}
