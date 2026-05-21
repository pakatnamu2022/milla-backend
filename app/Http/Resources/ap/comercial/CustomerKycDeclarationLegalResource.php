<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerKycDeclarationLegalResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $partner  = $this->businessPartner;
    $district = $this->officeDistrict;

    return [
      'id'                        => $this->id,
      'purchase_request_quote_id' => $this->purchase_request_quote_id,
      'purchase_request_quote'    => $this->purchaseRequestQuote?->fullCorrelative,
      'business_partner_id'       => $this->business_partner_id,
      'sede_id'                   => $this->sede_id,
      'status'                    => $this->status,

      // Snapshot del business_partner (persona jurídica)
      'bp_company_name' => $partner?->company_name ?? $partner?->full_name,
      'bp_ruc'          => $partner?->num_doc,
      'bp_email'        => $partner?->email,
      'bp_phone'        => $partner?->phone,

      // Campo 1-5
      'company_name'            => $this->company_name,
      'ruc'                     => $this->ruc,
      'foreign_registry_number' => $this->foreign_registry_number,
      'business_purpose'        => $this->business_purpose,
      'final_beneficiaries'     => $this->final_beneficiaries,
      'purpose_relationship'    => $this->purpose_relationship,

      // Campo 6 - Representante
      'rep_full_name'           => $this->rep_full_name,
      'rep_doc_type'            => $this->rep_doc_type,
      'rep_doc_number'          => $this->rep_doc_number,
      'rep_doc_other'           => $this->rep_doc_other,
      'rep_representation_type' => $this->rep_representation_type,
      'rep_instrument_type'     => $this->rep_instrument_type,
      'rep_escritura_date'      => $this->rep_escritura_date?->format('Y-m-d'),
      'rep_notary_name'         => $this->rep_notary_name,
      'rep_acta_certified_date' => $this->rep_acta_certified_date?->format('Y-m-d'),
      'rep_acta_date'           => $this->rep_acta_date?->format('Y-m-d'),
      'rep_instrument_other'    => $this->rep_instrument_other,
      'rep_registry_partition'  => $this->rep_registry_partition,
      'rep_registry_seat'       => $this->rep_registry_seat,
      'rep_registry_section'    => $this->rep_registry_section,
      'rep_registry_zone'       => $this->rep_registry_zone,

      // Campo 7 - Dirección oficina
      'office_street_type'  => $this->office_street_type,
      'office_street_name'  => $this->office_street_name,
      'office_number'       => $this->office_number,
      'office_int_number'   => $this->office_int_number,
      'office_urbanization' => $this->office_urbanization,
      'office_district_id'  => $this->office_district_id,
      'office_district'     => $district?->name,
      'office_province'     => $district?->province?->name,
      'office_department'   => $district?->province?->department?->name,
      'office_phone'        => $this->office_phone,

      // Campo 8 - Beneficiario
      'beneficiary_type'  => $this->beneficiary_type,
      'own_funds_origin'  => $this->own_funds_origin,

      'third_full_name'           => $this->third_full_name,
      'third_doc_type'            => $this->third_doc_type,
      'third_doc_number'          => $this->third_doc_number,
      'third_representation_type' => $this->third_representation_type,
      'third_pep_status'          => $this->third_pep_status,
      'third_pep_position'        => $this->third_pep_position,
      'third_pep_institution'     => $this->third_pep_institution,
      'third_funds_origin'        => $this->third_funds_origin,

      'entity_name'                => $this->entity_name,
      'entity_ruc'                 => $this->entity_ruc,
      'entity_representation_type' => $this->entity_representation_type,
      'entity_funds_origin'        => $this->entity_funds_origin,
      'entity_final_beneficiary'   => $this->entity_final_beneficiary,

      // Campo 9
      'account_number' => $this->account_number,

      'declaration_date'       => $this->declaration_date?->format('Y-m-d'),
      'signed_file_path'       => $this->signed_file_path,
      'legal_review_status'    => $this->legal_review_status,
      'legal_review_comments'  => $this->legal_review_comments,
      'reviewed_by'            => $this->reviewed_by,
      'reviewed_by_name'       => $this->reviewedBy?->name,
      'legal_review_at'        => $this->legal_review_at?->format('Y-m-d H:i:s'),
      'created_by'             => $this->created_by,
      'created_at'             => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'             => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
