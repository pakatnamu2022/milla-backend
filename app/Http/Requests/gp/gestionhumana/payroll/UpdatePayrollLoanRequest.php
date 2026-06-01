<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class UpdatePayrollLoanRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'concept_id'         => ['nullable', 'integer', 'exists:general_masters,id'],
            'worker_id'          => ['nullable', 'integer', 'exists:rrhh_persona,id'],
            'delivery_date'      => ['nullable', 'date'],
            'reason'             => ['nullable', 'string', 'max:255'],
            'payment_start'      => ['nullable', 'date'],
            'loan_amount'        => ['nullable', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1'],
            'installment_amount' => ['nullable', 'numeric', 'min:0'],
            'status'             => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'concept_id'         => 'concepto',
            'worker_id'          => 'trabajador',
            'delivery_date'      => 'fecha de entrega',
            'reason'             => 'motivo',
            'payment_start'      => 'inicio de pago',
            'loan_amount'        => 'monto del préstamo',
            'installments_count' => 'número de cuotas',
            'installment_amount' => 'monto de cuota',
        ];
    }

    public function messages(): array
    {
        return [
            'concept_id.exists'      => 'El :attribute seleccionado no existe.',
            'worker_id.exists'       => 'El :attribute seleccionado no existe.',
            'loan_amount.min'        => 'El :attribute no puede ser negativo.',
            'installments_count.min' => 'El :attribute debe ser al menos 1.',
            'installment_amount.min' => 'El :attribute no puede ser negativo.',
        ];
    }
}