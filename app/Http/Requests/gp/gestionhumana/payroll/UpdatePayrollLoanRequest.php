<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class UpdatePayrollLoanRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id'          => ['nullable', 'integer', 'exists:rrhh_persona,id'],
            'delivery_date'      => ['nullable', 'date'],
            'reason'             => ['nullable', 'string', 'max:255'],
            'payment_start'      => ['nullable', 'date'],
            'payment_days'       => ['nullable', 'array', 'min:1'],
            'payment_days.*'     => ['integer', 'min:1', 'max:31'],
            'loan_amount'        => ['nullable', 'numeric', 'min:0.01'],
            'installments_count' => ['nullable', 'integer', 'min:1'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'remaining_balance'  => ['nullable', 'numeric', 'min:0'],
            'status'             => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'worker_id'          => 'trabajador',
            'delivery_date'      => 'fecha de entrega',
            'reason'             => 'motivo',
            'payment_start'      => 'inicio de pago',
            'payment_days'       => 'días de pago',
            'payment_days.*'     => 'día de pago',
            'loan_amount'        => 'monto del préstamo',
            'installments_count' => 'número de cuotas',
            'installment_amount' => 'monto de cuota',
            'remaining_balance'  => 'saldo pendiente',
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.exists'       => 'El :attribute seleccionado no existe.',
            'loan_amount.min'        => 'El :attribute debe ser mayor a 0.',
            'installment_amount.min' => 'El :attribute debe ser mayor a 0.',
            'remaining_balance.min'  => 'El :attribute no puede ser negativo.',
            'payment_days.*.min'     => 'Cada :attribute debe ser al menos 1.',
            'payment_days.*.max'     => 'Cada :attribute no puede ser mayor a 31.',
        ];
    }
}