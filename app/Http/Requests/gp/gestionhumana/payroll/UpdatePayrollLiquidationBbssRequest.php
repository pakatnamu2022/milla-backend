<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class UpdatePayrollLiquidationBbssRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'period_id' => ['nullable', 'integer', 'exists:gh_payroll_periods,id'],
      'amount' => ['nullable', 'numeric', 'min:0'],
      'type_id' => ['nullable', 'integer', 'exists:gp_masters,id'],
      'status' => ['nullable', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'worker_id' => 'trabajador',
      'period_id' => 'periodo',
      'amount' => 'monto',
      'type_id' => 'tipo',
      'status' => 'estado',
    ];
  }

  public function messages(): array
  {
    return [
      'worker_id.exists' => 'El :attribute seleccionado no existe.',
      'period_id.exists' => 'El :attribute seleccionado no existe.',
      'amount.numeric' => 'El :attribute debe ser un número.',
      'amount.min' => 'El :attribute no puede ser negativo.',
      'type_id.exists' => 'El :attribute seleccionado no existe.',
    ];
  }
}
