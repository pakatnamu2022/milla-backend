<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollSubsidy;
use Illuminate\Validation\Rule;

class UpdatePayrollSubsidyRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(PayrollSubsidy::TYPES)],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
