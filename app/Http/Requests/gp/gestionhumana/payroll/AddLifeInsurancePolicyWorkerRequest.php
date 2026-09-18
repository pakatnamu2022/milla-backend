<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class AddLifeInsurancePolicyWorkerRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'insured_salary' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El trabajador es requerido',
            'worker_id.exists' => 'El trabajador seleccionado no existe',
            'insured_salary.min' => 'El sueldo asegurado debe ser mayor a cero',
        ];
    }
}
