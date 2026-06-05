<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollLoanRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'worker_id'          => ['required', 'integer', 'exists:rrhh_persona,id'],
            'delivery_date'      => ['nullable', 'date'],
            'reason'             => ['nullable', 'string', 'max:255'],
            'payment_start'      => ['nullable', 'date'],
            'payment_days'       => ['nullable', 'array', 'min:1'],
            'payment_days.*'     => ['integer', 'min:1', 'max:31'],
            'loan_amount'        => ['required', 'numeric', 'min:0.01'],
            'installments_count' => ['nullable', 'integer', 'min:1'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'worker_id.required'          => 'El :attribute es obligatorio.',
            'worker_id.exists'            => 'El :attribute seleccionado no existe.',
            'loan_amount.required'        => 'El :attribute es obligatorio.',
            'loan_amount.min'             => 'El :attribute debe ser mayor a 0.',
            'installment_amount.required' => 'El :attribute es obligatorio.',
            'installment_amount.min'      => 'El :attribute debe ser mayor a 0.',
            'payment_days.array'          => 'Los :attribute deben ser un listado.',
            'payment_days.*.integer'      => 'Cada :attribute debe ser un número entero.',
            'payment_days.*.min'          => 'Cada :attribute debe ser al menos 1.',
            'payment_days.*.max'          => 'Cada :attribute no puede ser mayor a 31.',
        ];
    }
}