<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdatePerDiemRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'employee_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'company_id' => ['nullable', 'integer', 'exists:companies,id'],
      'district_id' => ['nullable', 'integer', 'exists:district,id'],
      'per_diem_category_id' => ['nullable', 'integer', 'exists:gh_per_diem_category,id'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date', 'after:start_date'],
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

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'employee_id.exists' => 'El empleado seleccionado no existe.',
      'company_id.exists' => 'La empresa seleccionada no existe.',
      'district_id.exists' => 'El distrito seleccionado no existe.',
      'per_diem_category_id.exists' => 'La categoría seleccionada no existe.',
      'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
      'cash_amount.min' => 'El monto en efectivo debe ser mayor o igual a 0.',
      'transfer_amount.min' => 'El monto por transferencia debe ser mayor o igual a 0.',
      'budgets.*.expense_type_id.required_with' => 'El tipo de gasto es requerido.',
      'budgets.*.expense_type_id.exists' => 'El tipo de gasto seleccionado no existe.',
      'budgets.*.daily_amount.required_with' => 'El monto diario es requerido.',
      'budgets.*.daily_amount.min' => 'El monto diario debe ser mayor o igual a 0.',
      'budgets.*.days.required_with' => 'El número de días es requerido.',
      'budgets.*.days.min' => 'El número de días debe ser al menos 1.',
      'budgets.*.total.required_with' => 'El total es requerido.',
      'budgets.*.total.min' => 'El total debe ser mayor o igual a 0.',
    ];
  }

  /**
   * Get the validated data with additional computed fields
   */
  public function validated($key = null, $default = null)
  {
    $data = parent::validated($key, $default);

    // Calculate total budget if budgets are provided
    if (isset($data['budgets']) && is_array($data['budgets'])) {
      $totalBudget = 0;
      foreach ($data['budgets'] as $budget) {
        $totalBudget += $budget['total'];
      }
      $data['total_budget'] = $totalBudget;
    }

    return $data;
  }
}
