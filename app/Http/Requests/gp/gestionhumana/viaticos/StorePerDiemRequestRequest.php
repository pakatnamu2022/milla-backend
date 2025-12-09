<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class StorePerDiemRequestRequest extends FormRequest
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
            'employee_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'destination' => ['required', 'string', 'max:255'],
            'per_diem_category_id' => ['required', 'integer', 'exists:gh_per_diem_category,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'days_count' => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'string'],
            'final_result' => ['nullable', 'string'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'cash_amount' => ['nullable', 'numeric', 'min:0'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'budgets' => ['required', 'array', 'min:1'],
            'budgets.*.expense_type_id' => ['required', 'integer', 'exists:gh_expense_type,id'],
            'budgets.*.daily_amount' => ['required', 'numeric', 'min:0'],
            'budgets.*.days' => ['required', 'integer', 'min:1'],
            'budgets.*.total' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'El empleado es requerido.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'company_id.required' => 'La empresa es requerida.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'destination.required' => 'El destino es requerido.',
            'per_diem_category_id.required' => 'La categoría de viático es requerida.',
            'per_diem_category_id.exists' => 'La categoría seleccionada no existe.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'days_count.required' => 'El número de días es requerido.',
            'days_count.min' => 'El número de días debe ser al menos 1.',
            'purpose.required' => 'El propósito del viaje es requerido.',
            'budgets.required' => 'Debe incluir al menos un presupuesto.',
            'budgets.*.expense_type_id.required' => 'El tipo de gasto es requerido.',
            'budgets.*.expense_type_id.exists' => 'El tipo de gasto seleccionado no existe.',
            'budgets.*.daily_amount.required' => 'El monto diario es requerido.',
            'budgets.*.daily_amount.min' => 'El monto diario debe ser mayor o igual a 0.',
            'budgets.*.days.required' => 'El número de días es requerido.',
            'budgets.*.days.min' => 'El número de días debe ser al menos 1.',
            'budgets.*.total.required' => 'El total es requerido.',
            'budgets.*.total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }

    /**
     * Get the validated data with additional computed fields
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Generate unique code
        $year = date('Y');
        $prefix = "PDR-{$year}-";

        $lastRequest = \App\Models\gp\gestionhumana\viaticos\PerDiemRequest::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $data['code'] = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        // Set default status and values
        $data['status'] = 'draft';
        $data['paid'] = false;
        $data['settled'] = false;
        $data['total_spent'] = 0;
        $data['balance_to_return'] = 0;

        // Get current policy
        $currentPolicy = \App\Models\gp\gestionhumana\viaticos\PerDiemPolicy::current()->first();
        if ($currentPolicy) {
            $data['per_diem_policy_id'] = $currentPolicy->id;
        }

        // Calculate total budget from budgets array
        $totalBudget = 0;
        if (isset($data['budgets']) && is_array($data['budgets'])) {
            foreach ($data['budgets'] as $budget) {
                $totalBudget += $budget['total'];
            }
        }
        $data['total_budget'] = $totalBudget;

        return $data;
    }
}
