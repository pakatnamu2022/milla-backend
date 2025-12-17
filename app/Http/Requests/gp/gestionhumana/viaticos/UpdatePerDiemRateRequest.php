<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdatePerDiemRateRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'per_diem_policy_id' => ['sometimes', 'required', 'integer', 'exists:gh_per_diem_policy,id'],
      'district_id' => ['sometimes', 'required', 'integer', 'exists:gs_district,id'],
      'per_diem_category_id' => ['sometimes', 'required', 'integer', 'exists:gh_per_diem_category,id'],
      'expense_type_id' => ['sometimes', 'required', 'integer', 'exists:gh_expense_type,id'],
      'daily_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'active' => ['sometimes', 'required', 'boolean'],
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
      'active.required' => 'El estado es requerido.',
      'active.boolean' => 'El estado debe ser verdadero o falso.',
    ];
  }

  public function attributes(): array
  {
    return [
      'per_diem_policy_id' => 'Política de viáticos',
      'district_id' => 'Distrito',
      'per_diem_category_id' => 'Categoría',
      'expense_type_id' => 'Tipo de gasto',
      'daily_amount' => 'Monto diario',
      'active' => 'Estado',
    ];
  }
}
