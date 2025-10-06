<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateOpportunityActionRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'opportunity_id' => 'sometimes|integer|exists:ap_opportunity,id',
            'action_type_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
            'action_contact_type_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
            'datetime' => 'sometimes|date',
            'description' => 'nullable|string',
            'result' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'opportunity_id.integer' => 'La oportunidad debe ser un número entero.',
            'opportunity_id.exists' => 'La oportunidad seleccionada no existe.',

            'action_type_id.integer' => 'El tipo de acción debe ser un número entero.',
            'action_type_id.exists' => 'El tipo de acción seleccionado no existe.',

            'action_contact_type_id.integer' => 'El tipo de contacto debe ser un número entero.',
            'action_contact_type_id.exists' => 'El tipo de contacto seleccionado no existe.',

            'datetime.date' => 'La fecha y hora deben tener un formato válido.',

            'description.string' => 'La descripción debe ser una cadena de texto.',

            'result.boolean' => 'El resultado debe ser verdadero o falso.',
        ];
    }
}
