<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerKycDeclarationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $partner = $this->businessPartner;

    return [
      'id' => $this->id,
      'purchase_request_quote_id' => $this->purchase_request_quote_id,
      'purchase_request_quote' => $this->purchaseRequestQuote->fullCorrelative,
      'business_partner_id' => $this->business_partner_id,
      'sede_id' => $this->sede_id,
      'status' => $this->status,

      // Datos del cliente (snapshot de business_partners)
      'full_name' => $partner?->full_name,
      'first_name' => $partner?->first_name,
      'paternal_surname' => $partner?->paternal_surname,
      'maternal_surname' => $partner?->maternal_surname,
      'num_doc' => $partner?->num_doc,
      'document_type' => $partner?->documentType?->description,
      'document_type_id' => $partner?->document_type_id,
      'nationality' => $partner?->nationality,
      'marital_status' => $partner?->maritalStatus?->description,
      'marital_status_id' => $partner?->marital_status_id,
      'spouse_full_name' => $partner?->spouse_full_name,
      'direction' => $partner?->direction,
      'district' => $partner?->district?->name,
      'province' => $partner?->district?->province?->name,
      'department' => $partner?->district?->province?->department?->name,
      'phone' => $partner?->phone,
      'email' => $partner?->email,

      // Campos específicos de la DJ
      'occupation' => $this->occupation,
      'fixed_phone' => $this->fixed_phone,
      'purpose_relationship' => $this->purpose_relationship,

      // PEP 10.1
      'pep_status' => $this->pep_status,
      'pep_collaborator_status' => $this->pep_collaborator_status,
      'pep_position' => $this->pep_position,
      'pep_institution' => $this->pep_institution,

      // PEP 10.2
      'pep_relatives' => $this->pep_relatives,
      'pep_spouse_name' => $this->pep_spouse_name,

      // PEP 10.3
      'is_pep_relative' => $this->is_pep_relative,
      'pep_relative_data' => $this->pep_relative_data,

      // Beneficiario 11
      'beneficiary_type' => $this->beneficiary_type,
      'own_funds_origin' => $this->own_funds_origin,
      'third_full_name' => $this->third_full_name,
      'third_doc_type' => $this->third_doc_type,
      'third_doc_number' => $this->third_doc_number,
      'third_representation_type' => $this->third_representation_type,
      'third_pep_status' => $this->third_pep_status,
      'third_pep_position' => $this->third_pep_position,
      'third_pep_institution' => $this->third_pep_institution,
      'third_funds_origin' => $this->third_funds_origin,
      'entity_name' => $this->entity_name,
      'entity_ruc' => $this->entity_ruc,
      'entity_representation_type' => $this->entity_representation_type,
      'entity_funds_origin' => $this->entity_funds_origin,
      'entity_final_beneficiary' => $this->entity_final_beneficiary,

      'declaration_date' => $this->declaration_date?->format('Y-m-d'),
      'signed_file_path' => $this->signed_file_path,
      'legal_review_status' => $this->legal_review_status,
      'legal_review_comments' => $this->legal_review_comments,
      'reviewed_by' => $this->reviewed_by,
      'reviewed_by_name' => $this->reviewedBy?->name,
      'legal_review_at' => $this->legal_review_at?->format('Y-m-d H:i:s'),
      'created_by' => $this->created_by,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
