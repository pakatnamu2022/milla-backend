<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollLiquidationBbssRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:workers,id'],
            'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
            'amount'    => ['required', 'numeric', 'min:0'],
            'type'      => ['required', 'string', 'max:100'],
            'status'    => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'worker_id' => 'trabajador',
            'period_id' => 'periodo',
            'amount'    => 'monto',
            'type'      => 'tipo',
            'status'    => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required' => 'El :attribute es obligatorio.',
            'worker_id.exists'   => 'El :attribute seleccionado no existe.',
            'period_id.required' => 'El :attribute es obligatorio.',
            'period_id.exists'   => 'El :attribute seleccionado no existe.',
            'amount.required'    => 'El :attribute es obligatorio.',
            'amount.numeric'     => 'El :attribute debe ser un número.',
            'amount.min'         => 'El :attribute no puede ser negativo.',
            'type.required'      => 'El :attribute es obligatorio.',
            'type.max'           => 'El :attribute no debe exceder 100 caracteres.',
        ];
    }
}