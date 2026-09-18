<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StoreSctrRateRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'health_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'pension_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'effective_from' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida',
            'company_id.exists' => 'La empresa seleccionada no existe',
            'health_rate.required' => 'La tasa de SCTR salud es requerida',
            'health_rate.numeric' => 'La tasa de SCTR salud debe ser numérica (ej. 0.005 = 0.5%)',
            'health_rate.max' => 'La tasa de SCTR salud debe expresarse como fracción (ej. 0.005 = 0.5%)',
            'pension_rate.required' => 'La tasa de SCTR pensión es requerida',
            'pension_rate.numeric' => 'La tasa de SCTR pensión debe ser numérica (ej. 0.005 = 0.5%)',
            'pension_rate.max' => 'La tasa de SCTR pensión debe expresarse como fracción (ej. 0.005 = 0.5%)',
            'effective_from.required' => 'La fecha de inicio de vigencia es requerida',
            'effective_from.date' => 'La fecha de inicio de vigencia no es válida',
        ];
    }
}
