<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerDiemRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'destination' => ['nullable', 'string', 'max:255'],
            'per_diem_category_id' => ['nullable', 'integer', 'exists:gh_per_diem_category,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'days_count' => ['nullable', 'integer', 'min:1'],
            'purpose' => ['nullable', 'string'],
            'final_result' => ['nullable', 'string'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'budgets' => ['nullable', 'array'],
            'budgets.*.expense_type_id' => ['required_with:budgets', 'integer', 'exists:gh_expense_type,id'],
            'budgets.*.daily_amount' => ['required_with:budgets', 'numeric', 'min:0'],
            'budgets.*.days' => ['required_with:budgets', 'integer', 'min:1'],
            'budgets.*.total' => ['required_with:budgets', 'numeric', 'min:0'],
        ];
    }
}
