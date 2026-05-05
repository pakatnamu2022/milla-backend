<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\CustomerKycDeclaration;

class UpdateCustomerKycDeclarationRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'purchase_request_quote_id' => 'nullable|integer|exists:purchase_request_quote,id',
            'occupation' => 'nullable|string|max:255',
            'fixed_phone' => 'nullable|string|max:20',
            'purpose_relationship' => 'nullable|string|max:1000',

            'pep_status' => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_STATUSES),
            'pep_collaborator_status' => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_COLLABORATOR_STATUSES),
            'pep_position' => 'nullable|string|max:255',
            'pep_institution' => 'nullable|string|max:255',

            'pep_relatives' => 'nullable|array',
            'pep_relatives.*' => 'nullable|string|max:255',
            'pep_spouse_name' => 'nullable|string|max:255',

            'is_pep_relative' => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::PEP_RELATIVE_STATUSES),
            'pep_relative_data' => 'nullable|array',
            'pep_relative_data.*.pep_full_name' => 'nullable|string|max:255',
            'pep_relative_data.*.relationship' => 'nullable|string|max:255',

            'beneficiary_type' => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::BENEFICIARY_TYPES),
            'own_funds_origin' => 'nullable|string|max:1000',

            'third_full_name' => 'nullable|string|max:255',
            'third_doc_type' => 'nullable|string|max:50',
            'third_doc_number' => 'nullable|string|max:20',
            'third_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REPRESENTATION_TYPES),
            'third_pep_status' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::THIRD_PEP_STATUSES),
            'third_pep_position' => 'nullable|string|max:255',
            'third_pep_institution' => 'nullable|string|max:255',
            'third_funds_origin' => 'nullable|string|max:1000',

            'entity_name' => 'nullable|string|max:500',
            'entity_ruc' => 'nullable|string|max:20',
            'entity_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::ENTITY_REPRESENTATION_TYPES),
            'entity_funds_origin' => 'nullable|string|max:1000',
            'entity_final_beneficiary' => 'nullable|string|max:500',

            'declaration_date' => 'sometimes|date',
            'status' => 'sometimes|string|in:' . implode(',', CustomerKycDeclaration::STATUSES),
        ];
    }
}
