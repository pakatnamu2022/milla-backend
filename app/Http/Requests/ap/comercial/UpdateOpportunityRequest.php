<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateOpportunityRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => 'sometimes|integer|exists:rrhh_persona,id',
            'client_id' => 'sometimes|integer|exists:business_partners,id',
            'family_id' => 'sometimes|integer|exists:ap_families,id',
            'opportunity_type_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
            'client_status_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
            'opportunity_status_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.integer' => 'El trabajador debe ser un número entero.',
            'worker_id.exists' => 'El trabajador seleccionado no existe.',

            'client_id.integer' => 'El cliente debe ser un número entero.',
            'client_id.exists' => 'El cliente seleccionado no existe.',

            'family_id.integer' => 'La familia debe ser un número entero.',
            'family_id.exists' => 'La familia seleccionada no existe.',

            'opportunity_type_id.integer' => 'El tipo de oportunidad debe ser un número entero.',
            'opportunity_type_id.exists' => 'El tipo de oportunidad seleccionado no existe.',

            'client_status_id.integer' => 'El estado del cliente debe ser un número entero.',
            'client_status_id.exists' => 'El estado del cliente seleccionado no existe.',

            'opportunity_status_id.integer' => 'El estado de la oportunidad debe ser un número entero.',
            'opportunity_status_id.exists' => 'El estado de la oportunidad seleccionado no existe.',
        ];
    }
}
