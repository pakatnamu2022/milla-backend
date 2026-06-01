<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollLoanExtraDiscountRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'loan_id'         => ['required', 'integer', 'exists:gh_payroll_loans,id'],
            'concept_type_id' => ['required', 'integer', 'exists:general_masters,id'],
            'amount'          => ['required', 'numeric', 'min:0'],
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
            'loan_id.required'         => 'El :attribute es obligatorio.',
            'loan_id.exists'           => 'El :attribute seleccionado no existe.',
            'concept_type_id.required' => 'El :attribute es obligatorio.',
            'concept_type_id.exists'   => 'El :attribute seleccionado no existe.',
            'amount.required'          => 'El :attribute es obligatorio.',
            'amount.min'               => 'El :attribute no puede ser negativo.',
        ];
    }
}