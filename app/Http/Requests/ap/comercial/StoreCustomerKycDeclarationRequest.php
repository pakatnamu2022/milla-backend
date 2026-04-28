<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\CustomerKycDeclaration;

class StoreCustomerKycDeclarationRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'purchase_request_quote_id' => 'nullable|integer|exists:purchase_request_quote,id',
            'business_partner_id' => 'required|integer|exists:business_partners,id',
            'company_id' => 'required|integer|exists:companies,id',

            'occupation' => 'nullable|string|max:255',
            'fixed_phone' => 'nullable|string|max:20',
            'purpose_relationship' => 'nullable|string|max:1000',

            // Campo 10.1 PEP
            'pep_status' => 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_STATUSES),
            'pep_collaborator_status' => 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_COLLABORATOR_STATUSES),
            'pep_position' => 'nullable|string|max:255',
            'pep_institution' => 'nullable|string|max:255',

            // Campo 10.2
            'pep_relatives' => 'nullable|array',
            'pep_relatives.*' => 'nullable|string|max:255',
            'pep_spouse_name' => 'nullable|string|max:255',

            // Campo 10.3
            'is_pep_relative' => 'required|string|in:' . implode(',', CustomerKycDeclaration::PEP_RELATIVE_STATUSES),
            'pep_relative_data' => 'nullable|array',
            'pep_relative_data.*.pep_full_name' => 'nullable|string|max:255',
            'pep_relative_data.*.relationship' => 'nullable|string|max:255',

            // Campo 11
            'beneficiary_type' => 'required|string|in:' . implode(',', CustomerKycDeclaration::BENEFICIARY_TYPES),

            // 11.1
            'own_funds_origin' => 'nullable|string|max:1000',

            // 11.2
            'third_full_name' => 'nullable|string|max:255',
            'third_doc_type' => 'nullable|string|max:50',
            'third_doc_number' => 'nullable|string|max:20',
            'third_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::REPRESENTATION_TYPES),
            'third_pep_status' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::THIRD_PEP_STATUSES),
            'third_pep_position' => 'nullable|string|max:255',
            'third_pep_institution' => 'nullable|string|max:255',
            'third_funds_origin' => 'nullable|string|max:1000',

            // 11.3
            'entity_name' => 'nullable|string|max:500',
            'entity_ruc' => 'nullable|string|max:20',
            'entity_representation_type' => 'nullable|string|in:' . implode(',', CustomerKycDeclaration::ENTITY_REPRESENTATION_TYPES),
            'entity_funds_origin' => 'nullable|string|max:1000',
            'entity_final_beneficiary' => 'nullable|string|max:500',

            'declaration_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'business_partner_id.required' => 'El cliente es obligatorio.',
            'business_partner_id.exists' => 'El cliente seleccionado no existe.',
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'pep_status.required' => 'El estado PEP es obligatorio.',
            'pep_status.in' => 'El estado PEP no es válido.',
            'pep_collaborator_status.required' => 'El estado de colaborador PEP es obligatorio.',
            'pep_collaborator_status.in' => 'El estado de colaborador PEP no es válido.',
            'is_pep_relative.required' => 'El estado de pariente PEP es obligatorio.',
            'is_pep_relative.in' => 'El estado de pariente PEP no es válido.',
            'beneficiary_type.required' => 'El tipo de beneficiario es obligatorio.',
            'beneficiary_type.in' => 'El tipo de beneficiario no es válido.',
            'declaration_date.required' => 'La fecha de declaración es obligatoria.',
            'declaration_date.date' => 'La fecha de declaración debe ser una fecha válida.',
        ];
    }
}
