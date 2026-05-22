<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\CustomerKycDeclaration;

class StoreCustomerKycDeclarationRequest extends StoreRequest
{
  public function rules(): array
  {
    $isNatural  = $this->input('person_type') === CustomerKycDeclaration::PERSON_TYPE_NATURAL;
    $isJuridica = $this->input('person_type') === CustomerKycDeclaration::PERSON_TYPE_JURIDICA;

    return [
      'purchase_request_quote_id' => 'nullable|integer|exists:purchase_request_quote,id',
      'business_partner_id'       => 'required|integer|exists:business_partners,id',
      'sede_id'                   => 'required|integer|exists:config_sede,id',
      'person_type'               => 'required|string|in:' . implode(',', CustomerKycDeclaration::PERSON_TYPES),
      'declaration_date'          => 'required|date',

      // ── Compartidos ──────────────────────────────────────────────────────
      'purpose_relationship' => 'nullable|string|max:1000',
      'beneficiary_type'     => 'required|string|in:' . implode(',', CustomerKycDeclaration::BENEFICIARY_TYPES),
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

      // ── Solo Persona Natural ─────────────────────────────────────────────
      'occupation'  => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'cargo'       => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'fixed_phone' => $isNatural ? 'nullable|string|max:20'  : 'prohibited',

      'pep_status'              => $isNatural ? 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_STATUSES)              : 'prohibited',
      'pep_collaborator_status' => $isNatural ? 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_COLLABORATOR_STATUSES) : 'prohibited',
      'pep_position'            => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_institution'         => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_relatives'           => $isNatural ? 'nullable|array'          : 'prohibited',
      'pep_relatives.*'         => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_spouse_name'         => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'is_pep_relative'         => $isNatural ? 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_RELATIVE_STATUSES) : 'prohibited',
      'pep_relative_data'                    => $isNatural ? 'nullable|array'          : 'prohibited',
      'pep_relative_data.*.pep_full_name' => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_relative_data.*.relationship'  => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_relative_data.*.cargo'         => $isNatural ? 'nullable|string|max:255' : 'prohibited',
      'pep_relative_data.*.institution'   => $isNatural ? 'nullable|string|max:255' : 'prohibited',

      // ── Solo Persona Jurídica ────────────────────────────────────────────
      'company_name'            => $isJuridica ? 'nullable|string|max:500'  : 'prohibited',
      'ruc'                     => $isJuridica ? 'nullable|string|max:20'   : 'prohibited',
      'foreign_registry_number' => $isJuridica ? 'nullable|string|max:100'  : 'prohibited',
      'business_purpose'        => $isJuridica ? 'nullable|string|max:2000' : 'prohibited',
      'final_beneficiaries'     => $isJuridica ? 'nullable|string|max:2000' : 'prohibited',

      'rep_full_name'           => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
      'rep_doc_type'            => $isJuridica ? 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_DOC_TYPES)            : 'prohibited',
      'rep_doc_number'          => $isJuridica ? 'nullable|string|max:20'  : 'prohibited',
      'rep_doc_other'           => $isJuridica ? 'nullable|string|max:100' : 'prohibited',
      'rep_representation_type' => $isJuridica ? 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_REPRESENTATION_TYPES) : 'prohibited',
      'rep_instrument_type'     => $isJuridica ? 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REP_INSTRUMENT_TYPES)     : 'prohibited',
      'rep_escritura_date'      => $isJuridica ? 'nullable|date'           : 'prohibited',
      'rep_notary_name'         => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
      'rep_acta_certified_date' => $isJuridica ? 'nullable|date'           : 'prohibited',
      'rep_acta_date'           => $isJuridica ? 'nullable|date'           : 'prohibited',
      'rep_instrument_other'    => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
      'rep_registry_partition'  => $isJuridica ? 'nullable|string|max:100' : 'prohibited',
      'rep_registry_seat'       => $isJuridica ? 'nullable|string|max:100' : 'prohibited',
      'rep_registry_section'    => $isJuridica ? 'nullable|string|max:100' : 'prohibited',
      'rep_registry_zone'       => $isJuridica ? 'nullable|string|max:100' : 'prohibited',

      'office_street_type'  => $isJuridica ? 'nullable|string|in:' . implode(',', CustomerKycDeclaration::OFFICE_STREET_TYPES) : 'prohibited',
      'office_street_name'  => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
      'office_number'       => $isJuridica ? 'nullable|string|max:20'  : 'prohibited',
      'office_int_number'   => $isJuridica ? 'nullable|string|max:20'  : 'prohibited',
      'office_urbanization' => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
      'office_district_id'  => $isJuridica ? 'nullable|integer|exists:district,id' : 'prohibited',
      'office_phone'        => $isJuridica ? 'nullable|string|max:30'  : 'prohibited',

      'account_number' => $isJuridica ? 'nullable|string|max:255' : 'prohibited',
    ];
  }

  public function messages(): array
  {
    return [
      'business_partner_id.required' => 'El cliente es obligatorio.',
      'business_partner_id.exists'   => 'El cliente seleccionado no existe.',
      'sede_id.required'             => 'La sede es obligatoria.',
      'person_type.required'         => 'El tipo de persona es obligatorio.',
      'person_type.in'               => 'El tipo de persona no es válido.',
      'pep_status.required'          => 'El estado PEP es obligatorio.',
      'pep_collaborator_status.required' => 'El estado de colaborador PEP es obligatorio.',
      'is_pep_relative.required'     => 'El estado de pariente PEP es obligatorio.',
      'beneficiary_type.required'    => 'El tipo de beneficiario es obligatorio.',
      'beneficiary_type.in'          => 'El tipo de beneficiario no es válido.',
      'declaration_date.required'    => 'La fecha de declaración es obligatoria.',
      'declaration_date.date'        => 'La fecha de declaración debe ser una fecha válida.',
    ];
  }
}
