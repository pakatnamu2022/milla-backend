<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdateRequestBudgetRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'per_diem_request_id' => ['sometimes', 'required', 'integer', 'exists:gh_per_diem_request,id'],
      'expense_type_id' => ['sometimes', 'required', 'integer', 'exists:gh_expense_type,id'],
      'daily_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
      'days' => ['sometimes', 'required', 'integer', 'min:1'],
      'total' => ['sometimes', 'required', 'numeric', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'per_diem_request_id.required' => 'La solicitud de viático es requerida.',
      'per_diem_request_id.exists' => 'La solicitud de viático seleccionada no existe.',
      'expense_type_id.required' => 'El tipo de gasto es requerido.',
      'expense_type_id.exists' => 'El tipo de gasto seleccionado no existe.',
      'daily_amount.required' => 'El monto diario es requerido.',
      'daily_amount.numeric' => 'El monto diario debe ser un número.',
      'daily_amount.min' => 'El monto diario debe ser mayor o igual a 0.',
      'days.required' => 'Los días son requeridos.',
      'days.integer' => 'Los días deben ser un número entero.',
      'days.min' => 'Los días deben ser al menos 1.',
      'total.required' => 'El total es requerido.',
      'total.numeric' => 'El total debe ser un número.',
      'total.min' => 'El total debe ser mayor o igual a 0.',
    ];
  }

  public function attributes(): array
  {
    return [
      'per_diem_request_id' => 'Solicitud de viático',
      'expense_type_id' => 'Tipo de gasto',
      'daily_amount' => 'Monto diario',
      'days' => 'Días',
      'total' => 'Total',
    ];
  }
}
