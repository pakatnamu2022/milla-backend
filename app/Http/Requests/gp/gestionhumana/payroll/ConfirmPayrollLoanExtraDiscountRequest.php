<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class ConfirmPayrollLoanExtraDiscountRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'monto',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'El :attribute debe ser mayor a 0.',
        ];
    }
}