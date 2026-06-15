<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use Illuminate\Foundation\Http\FormRequest;

class ExportPayrollRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => 'required|integer|exists:gh_payroll_periods,id',
        ];
    }
}