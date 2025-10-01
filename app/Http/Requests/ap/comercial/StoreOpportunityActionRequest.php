<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreOpportunityActionRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'opportunity_id' => 'required|integer|exists:ap_opportunity,id',
            'action_type_id' => 'required|integer|exists:ap_commercial_masters,id',
            'action_contact_type_id' => 'required|integer|exists:ap_commercial_masters,id',
            'datetime' => 'required|date',
            'description' => 'nullable|string',
            'result' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'opportunity_id.required' => 'La oportunidad es obligatoria.',
            'opportunity_id.integer' => 'La oportunidad debe ser un número entero.',
            'opportunity_id.exists' => 'La oportunidad seleccionada no existe.',

            'action_type_id.required' => 'El tipo de acción es obligatorio.',
            'action_type_id.integer' => 'El tipo de acción debe ser un número entero.',
            'action_type_id.exists' => 'El tipo de acción seleccionado no existe.',

            'action_contact_type_id.required' => 'El tipo de contacto es obligatorio.',
            'action_contact_type_id.integer' => 'El tipo de contacto debe ser un número entero.',
            'action_contact_type_id.exists' => 'El tipo de contacto seleccionado no existe.',

            'datetime.required' => 'La fecha y hora son obligatorias.',
            'datetime.date' => 'La fecha y hora deben tener un formato válido.',

            'description.string' => 'La descripción debe ser una cadena de texto.',

            'result.required' => 'El resultado es obligatorio.',
            'result.boolean' => 'El resultado debe ser verdadero o falso.',
        ];
    }
}
