<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StorePerDiemRateRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'per_diem_policy_id' => ['required', 'integer', 'exists:gh_per_diem_policy,id'],
      'district_id' => ['required', 'integer', 'exists:district,id'],
      'expense_type_id' => ['required', 'integer', 'exists:gh_expense_type,id'],
      'per_diem_category_id' => ['required', 'integer', 'exists:gh_per_diem_category,id'],
      'daily_amount' => ['required', 'numeric', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'per_diem_policy_id.required' => 'La política de viáticos es requerida.',
      'per_diem_policy_id.exists' => 'La política de viáticos seleccionada no existe.',
      'district_id.required' => 'El distrito es requerido.',
      'district_id.exists' => 'El distrito seleccionado no existe.',
      'per_diem_category_id.required' => 'La categoría es requerida.',
      'per_diem_category_id.exists' => 'La categoría seleccionada no existe.',
      'expense_type_id.required' => 'El tipo de gasto es requerido.',
      'expense_type_id.exists' => 'El tipo de gasto seleccionado no existe.',
      'daily_amount.required' => 'El monto diario es requerido.',
      'daily_amount.numeric' => 'El monto diario debe ser un número.',
      'daily_amount.min' => 'El monto diario debe ser mayor o igual a 0.',
    ];
  }
}
