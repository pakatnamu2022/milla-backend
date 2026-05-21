<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexCustomerKycDeclarationLegalRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'business_partner_id'  => 'nullable|integer|exists:business_partners,id',
      'sede_id'              => 'nullable|integer|exists:config_sede,id',
      'beneficiary_type'     => 'nullable|string',
      'status'               => 'nullable|string',
      'legal_review_status'  => 'nullable|string',
      'declaration_date'     => 'nullable|date',
      'per_page'             => 'nullable|integer|min:1|max:200',
      'page'                 => 'nullable|integer|min:1',
      'sort_by'              => 'nullable|string',
      'sort_order'           => 'nullable|string|in:asc,desc',
    ];
  }
}
