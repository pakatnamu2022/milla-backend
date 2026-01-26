<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class CalculatePayrollRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
      'worker_ids' => ['nullable', 'array'],
      'worker_ids.*' => ['integer', 'exists:rrhh_persona,id'],
    ];
  }

  public function attributes(): array
  {
    return [
      'period_id' => 'period',
      'worker_ids' => 'workers',
    ];
  }
}
