<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;

class IndexCustomerKycDeclarationRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'business_partner_id' => 'nullable|integer|exists:business_partners,id',
      'sede_id'             => 'nullable|integer|exists:config_sede,id',
      'person_type'         => 'nullable|string|in:NATURAL,JURIDICA',
      'beneficiary_type'    => 'nullable|string',
      'status'              => 'nullable|string',
      'legal_review_status' => 'nullable|string',
      'is_signed'           => 'nullable|boolean',
      'declaration_date'    => 'nullable|date',
      'per_page' => 'nullable|integer|min:1|max:200',
      'page' => 'nullable|integer|min:1',
      'sort_by' => 'nullable|string',
      'sort_order' => 'nullable|string|in:asc,desc',
    ];
  }
}
