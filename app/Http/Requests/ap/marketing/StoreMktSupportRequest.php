<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class StoreMktSupportRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'activity_id'       => 'nullable|integer|exists:ap_mkt_activities,id',
      'type'              => 'required|string|in:receipt,invoice,photo,report,other',
      'document_series'   => 'nullable|string|max:10',
      'document_number'   => 'nullable|string|max:20',
      'issue_date'        => 'nullable|date',
      'supplier_id'       => 'nullable|integer|exists:business_partners,id',
      'currency_id'       => 'nullable|integer|exists:type_currency,id',
      'amount'            => 'required|numeric|min:0.01',
      'files'             => 'nullable|array|max:10',
      'files.*'           => 'file|max:10240|mimes:jpg,jpeg,png,pdf',
      'notes'             => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'activity_id'       => 'actividad',
      'type'              => 'tipo',
      'document_series'   => 'serie',
      'document_number'   => 'número de documento',
      'issue_date'        => 'fecha de emisión',
      'supplier_id'       => 'proveedor',
      'currency_id'       => 'moneda',
      'amount'            => 'monto',
      'files'             => 'archivos',
      'notes'             => 'notas',
    ];
  }
}
