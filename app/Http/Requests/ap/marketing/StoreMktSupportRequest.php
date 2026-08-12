<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktSupportRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'activity_id'       => 'nullable|integer|exists:mkt_activities,id',
      'purchase_order_id' => 'nullable|integer|exists:mkt_purchase_orders,id',
      'type'              => 'required|string|in:receipt,invoice,photo,report,other',
      'document_series'   => 'nullable|string|max:10',
      'document_number'   => 'nullable|string|max:20',
      'issue_date'        => 'nullable|date',
      'supplier_id'       => 'nullable|integer|exists:business_partners,id',
      'currency_id'       => 'nullable|integer|exists:type_currency,id',
      'amount'            => 'nullable|numeric|min:0',
      'file_path'         => 'nullable|string|max:500',
      'notes'             => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'activity_id'       => 'actividad',
      'purchase_order_id' => 'orden de compra',
      'type'              => 'tipo',
      'document_series'   => 'serie',
      'document_number'   => 'número de documento',
      'issue_date'        => 'fecha de emisión',
      'supplier_id'       => 'proveedor',
      'currency_id'       => 'moneda',
      'amount'            => 'monto',
      'file_path'         => 'archivo',
      'notes'             => 'notas',
    ];
  }
}
