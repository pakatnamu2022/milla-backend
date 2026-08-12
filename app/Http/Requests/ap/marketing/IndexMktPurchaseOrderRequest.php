<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktPurchaseOrderRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'activity_id' => 'nullable|integer|exists:ap_mkt_activities,id',
      'proposal_id' => 'nullable|integer|exists:ap_mkt_proposals,id',
      'supplier_id' => 'nullable|integer|exists:business_partners,id',
      'status'      => 'nullable|string|in:draft,sent,in_execution,pending_support,supported,pending_billing,billed,closed,cancelled',
      'currency_id' => 'nullable|integer|exists:type_currency,id',
      'search'      => 'nullable|string|max:50',
    ]);
  }
}
