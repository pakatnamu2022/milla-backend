<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class UpdatePayrollLoanExtraDiscountRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'loan_id'         => ['nullable', 'integer', 'exists:gh_payroll_loans,id'],
            'concept_type_id' => ['nullable', 'integer', 'exists:general_masters,id'],
            'amount'          => ['nullable', 'numeric', 'min:0'],
            'applied'         => ['nullable', 'boolean'],
            'status'          => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'loan_id'         => 'préstamo',
            'concept_type_id' => 'tipo de concepto',
            'amount'          => 'monto',
            'applied'         => 'aplicado',
        ];
    }

    public function messages(): array
    {
        return [
            'loan_id.exists'         => 'El :attribute seleccionado no existe.',
            'concept_type_id.exists' => 'El :attribute seleccionado no existe.',
            'amount.min'             => 'El :attribute no puede ser negativo.',
        ];
    }
}