<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateApVehicleDocumentsRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'document_type' => 'sometimes|required|string|max:100',
            'issuer_type' => 'sometimes|required|string|max:100',
            'document_series' => 'nullable|string|max:20',
            'document_number' => 'nullable|string|max:50',
            'issue_date' => 'nullable|date',
            'requires_sunat' => 'nullable|boolean',
            'is_sunat_registered' => 'nullable|boolean',
            'vehicle_movement_id' => 'sometimes|required|integer|exists:ap_vehicle_movement,id',
            'transmitter_id' => 'sometimes|required|integer|exists:business_partners,id',
            'receiver_id' => 'sometimes|required|integer|exists:business_partners,id',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
            'driver_doc' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'license' => 'nullable|string|max:255',
            'plate' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'cancellation_reason' => 'nullable|string',
            'cancelled_by' => 'nullable|integer|exists:usr_users,id',
            'cancelled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|boolean',
            'transfer_reason_id' => 'nullable|integer|exists:sunat_concepts,id',
            'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',
        ];
    }
}
