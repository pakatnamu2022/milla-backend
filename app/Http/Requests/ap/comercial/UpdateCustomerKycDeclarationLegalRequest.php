<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\CustomerKycDeclarationLegal;

class UpdateCustomerKycDeclarationLegalRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'purchase_request_quote_id' => 'nullable|integer|exists:purchase_request_quote,id',

      'company_name'            => 'nullable|string|max:500',
      'ruc'                     => 'nullable|string|max:20',
      'foreign_registry_number' => 'nullable|string|max:100',
      'business_purpose'        => 'nullable|string|max:2000',
      'final_beneficiaries'     => 'nullable|string|max:2000',
      'purpose_relationship'    => 'nullable|string|max:1000',

      'rep_full_name'           => 'nullable|string|max:255',
      'rep_doc_type'            => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::REP_DOC_TYPES),
      'rep_doc_number'          => 'nullable|string|max:20',
      'rep_doc_other'           => 'nullable|string|max:100',
      'rep_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::REP_REPRESENTATION_TYPES),
      'rep_instrument_type'     => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::REP_INSTRUMENT_TYPES),
      'rep_escritura_date'      => 'nullable|date',
      'rep_notary_name'         => 'nullable|string|max:255',
      'rep_acta_certified_date' => 'nullable|date',
      'rep_acta_date'           => 'nullable|date',
      'rep_instrument_other'    => 'nullable|string|max:255',
      'rep_registry_partition'  => 'nullable|string|max:100',
      'rep_registry_seat'       => 'nullable|string|max:100',
      'rep_registry_section'    => 'nullable|string|max:100',
      'rep_registry_zone'       => 'nullable|string|max:100',

      'office_street_type'  => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::OFFICE_STREET_TYPES),
      'office_street_name'  => 'nullable|string|max:255',
      'office_number'       => 'nullable|string|max:20',
      'office_int_number'   => 'nullable|string|max:20',
      'office_urbanization' => 'nullable|string|max:255',
      'office_district_id'  => 'nullable|integer|exists:district,id',
      'office_phone'        => 'nullable|string|max:30',

      'beneficiary_type'   => 'sometimes|string|in:' . implode(',', CustomerKycDeclarationLegal::BENEFICIARY_TYPES),
      'own_funds_origin'   => 'nullable|string|max:1000',

      'third_full_name'           => 'nullable|string|max:255',
      'third_doc_type'            => 'nullable|string|max:50',
      'third_doc_number'          => 'nullable|string|max:20',
      'third_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::THIRD_REPRESENTATION_TYPES),
      'third_pep_status'          => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::THIRD_PEP_STATUSES),
      'third_pep_position'        => 'nullable|string|max:255',
      'third_pep_institution'     => 'nullable|string|max:255',
      'third_funds_origin'        => 'nullable|string|max:1000',

      'entity_name'                => 'nullable|string|max:500',
      'entity_ruc'                 => 'nullable|string|max:20',
      'entity_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclarationLegal::ENTITY_REPRESENTATION_TYPES),
      'entity_funds_origin'        => 'nullable|string|max:1000',
      'entity_final_beneficiary'   => 'nullable|string|max:1000',

      'account_number'  => 'nullable|string|max:255',
      'declaration_date' => 'sometimes|date',
      'status'           => 'sometimes|string|in:' . implode(',', CustomerKycDeclarationLegal::STATUSES),
    ];
  }
}
