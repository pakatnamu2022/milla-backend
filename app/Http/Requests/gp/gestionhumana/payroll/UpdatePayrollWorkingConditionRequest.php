<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class UpdatePayrollWorkingConditionRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'monto',
            'status' => 'estado',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'El :attribute no puede ser negativo.',
        ];
    }
}
