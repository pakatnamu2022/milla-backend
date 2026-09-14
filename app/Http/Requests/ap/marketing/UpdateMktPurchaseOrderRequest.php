<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class UpdateMktPurchaseOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'plan_id'     => 'nullable|integer|exists:ap_mkt_plans,id',
      'activity_id' => 'nullable|integer|exists:ap_mkt_activities,id',
      'proposal_id' => 'nullable|integer|exists:ap_mkt_proposals,id',
      'supplier_id' => 'nullable|integer|exists:business_partners,id',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
      'number'      => 'nullable|string|max:50',
      'reference'   => 'nullable|string|max:100',
      'amount'      => 'nullable|numeric|min:0',
      'issue_date'  => 'nullable|date',
      'status'      => 'nullable|string|in:draft,sent,in_execution,pending_support,supported,pending_billing,billed,closed,cancelled',
      'notes'       => 'nullable|string',
      'file_path'   => 'nullable|string|max:500',
      'file'        => 'nullable|file|max:10240|mimes:pdf',
    ];
  }

  public function attributes(): array
  {
    return [
      'plan_id'     => 'plan',
      'activity_id' => 'actividad',
      'proposal_id' => 'propuesta',
      'supplier_id' => 'proveedor',
      'currency_id' => 'moneda',
      'number'      => 'número',
      'reference'   => 'referencia',
      'amount'      => 'monto',
      'issue_date'  => 'fecha de emisión',
      'status'      => 'estado',
      'notes'       => 'notas',
      'file'        => 'archivo PDF',
    ];
  }
}
