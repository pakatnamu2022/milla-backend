<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\CustomerKycDeclaration;

class UpdateCustomerKycDeclarationRequest extends StoreRequest
{
  public function rules(): array
  {
    // En update se respeta el person_type del body si viene, si no se usa el del registro
    $personType = $this->input('person_type');
    $isNatural  = $personType === CustomerKycDeclaration::PERSON_TYPE_NATURAL;
    $isJuridica = $personType === CustomerKycDeclaration::PERSON_TYPE_JURIDICA;
    $typeKnown  = $isNatural || $isJuridica;

    return [
      'purchase_request_quote_id' => 'nullable|integer|exists:purchase_request_quote,id',
      'person_type'               => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PERSON_TYPES),
      'declaration_date'          => 'sometimes|date',
      'status'                    => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::STATUSES),

      // ── Compartidos ──────────────────────────────────────────────────────
      'purpose_relationship' => 'nullable|string|max:1000',
      'beneficiary_type'     => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::BENEFICIARY_TYPES),
      'own_funds_origin'     => 'nullable|string|max:1000',

      'third_full_name'           => 'nullable|string|max:255',
      'third_doc_type'            => 'nullable|string|max:50',
      'third_doc_number'          => 'nullable|string|max:20',
      'third_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::THIRD_REPRESENTATION_TYPES),
      'third_pep_status'          => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::THIRD_PEP_STATUSES),
      'third_pep_position'        => 'nullable|string|max:255',
      'third_pep_institution'     => 'nullable|string|max:255',
      'third_funds_origin'        => 'nullable|string|max:1000',

      'entity_name'                => 'nullable|string|max:500',
      'entity_ruc'                 => 'nullable|string|max:20',
      'entity_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::ENTITY_REPRESENTATION_TYPES),
      'entity_funds_origin'        => 'nullable|string|max:1000',
      'entity_final_beneficiary'   => 'nullable|string|max:1000',

      // ── Solo Persona Natural (prohibido si se sabe que es Jurídica) ──────
      'occupation'              => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'fixed_phone'             => $isJuridica ? 'prohibited' : 'nullable|string|max:20',
      'pep_status'              => $isJuridica ? 'prohibited' : 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_STATUSES),
      'pep_collaborator_status' => $isJuridica ? 'prohibited' : 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_COLLABORATOR_STATUSES),
      'pep_position'            => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'pep_institution'         => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'pep_relatives'           => $isJuridica ? 'prohibited' : 'nullable|array',
      'pep_relatives.*'         => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'pep_spouse_name'         => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'is_pep_relative'         => $isJuridica ? 'prohibited' : 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_RELATIVE_STATUSES),
      'pep_relative_data'                 => $isJuridica ? 'prohibited' : 'nullable|array',
      'pep_relative_data.*.pep_full_name' => $isJuridica ? 'prohibited' : 'nullable|string|max:255',
      'pep_relative_data.*.relationship'  => $isJuridica ? 'prohibited' : 'nullable|string|max:255',

      // ── Solo Persona Jurídica (prohibido si se sabe que es Natural) ──────
      'company_name'            => $isNatural ? 'prohibited' : 'nullable|string|max:500',
      'ruc'                     => $isNatural ? 'prohibited' : 'nullable|string|max:20',
      'foreign_registry_number' => $isNatural ? 'prohibited' : 'nullable|string|max:100',
      'business_purpose'        => $isNatural ? 'prohibited' : 'nullable|string|max:2000',
      'final_beneficiaries'     => $isNatural ? 'prohibited' : 'nullable|string|max:2000',

      'rep_full_name'           => $isNatural ? 'prohibited' : 'nullable|string|max:255',
      'rep_doc_type'            => $isNatural ? 'prohibited' : 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_DOC_TYPES),
      'rep_doc_number'          => $isNatural ? 'prohibited' : 'nullable|string|max:20',
      'rep_doc_other'           => $isNatural ? 'prohibited' : 'nullable|string|max:100',
      'rep_representation_type' => $isNatural ? 'prohibited' : 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_REPRESENTATION_TYPES),
      'rep_instrument_type'     => $isNatural ? 'prohibited' : 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_INSTRUMENT_TYPES),
      'rep_escritura_date'      => $isNatural ? 'prohibited' : 'nullable|date',
      'rep_notary_name'         => $isNatural ? 'prohibited' : 'nullable|string|max:255',
      'rep_acta_certified_date' => $isNatural ? 'prohibited' : 'nullable|date',
      'rep_acta_date'           => $isNatural ? 'prohibited' : 'nullable|date',
      'rep_instrument_other'    => $isNatural ? 'prohibited' : 'nullable|string|max:255',
      'rep_registry_partition'  => $isNatural ? 'prohibited' : 'nullable|string|max:100',
      'rep_registry_seat'       => $isNatural ? 'prohibited' : 'nullable|string|max:100',
      'rep_registry_section'    => $isNatural ? 'prohibited' : 'nullable|string|max:100',
      'rep_registry_zone'       => $isNatural ? 'prohibited' : 'nullable|string|max:100',

      'office_street_type'  => $isNatural ? 'prohibited' : 'nullable|string|in:' . implode(',', CustomerKycDeclaration::OFFICE_STREET_TYPES),
      'office_street_name'  => $isNatural ? 'prohibited' : 'nullable|string|max:255',
      'office_number'       => $isNatural ? 'prohibited' : 'nullable|string|max:20',
      'office_int_number'   => $isNatural ? 'prohibited' : 'nullable|string|max:20',
      'office_urbanization' => $isNatural ? 'prohibited' : 'nullable|string|max:255',
      'office_district_id'  => $isNatural ? 'prohibited' : 'nullable|integer|exists:district,id',
      'office_phone'        => $isNatural ? 'prohibited' : 'nullable|string|max:30',

      'account_number' => $isNatural ? 'prohibited' : 'nullable|string|max:255',
    ];
  }
}
