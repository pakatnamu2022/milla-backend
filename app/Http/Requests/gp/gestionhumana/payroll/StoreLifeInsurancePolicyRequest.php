<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StoreLifeInsurancePolicyRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'insurer' => ['nullable', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:366'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'igv_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'exclusion' => ['nullable', 'numeric', 'min:0'],
            'net_premium' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La empresa es requerida',
            'company_id.exists' => 'La empresa seleccionada no existe',
            'start_date.required' => 'La fecha de inicio de la póliza es requerida',
            'end_date.required' => 'La fecha de fin de la póliza es requerida',
            'end_date.after' => 'La fecha de fin debe ser posterior a la de inicio',
            'monthly_rate.max' => 'La tasa mensual debe expresarse como fracción (ej. 0.0026 = 0.26%)',
            'igv_rate.max' => 'El IGV debe expresarse como fracción (ej. 0.18 = 18%)',
        ];
    }
}
