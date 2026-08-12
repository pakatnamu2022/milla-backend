<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\IndexRequest;

class IndexMktSupportRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'activity_id'       => 'nullable|integer|exists:ap_mkt_activities,id',
      'purchase_order_id' => 'nullable|integer|exists:ap_mkt_purchase_orders,id',
      'type'              => 'nullable|string|in:receipt,invoice,photo,report,other',
      'supplier_id'       => 'nullable|integer|exists:business_partners,id',
      'currency_id'       => 'nullable|integer|exists:type_currency,id',
      'search'            => 'nullable|string|max:50',
    ]);
  }
}
