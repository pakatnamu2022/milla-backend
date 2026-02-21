<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;

class StorePayrollScheduleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'code' => ['required', 'string', 'max:50'],
      'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
      'work_date' => ['required', 'date'],
      'notes' => ['nullable', 'string', 'max:255'],
      'status' => ['nullable', 'string', 'in:' . implode(',', PayrollSchedule::STATUSES)],
    ];
  }

  public function attributes(): array
  {
    return [
      'worker_id' => 'worker',
      'code' => 'attendance code',
      'period_id' => 'period',
      'work_date' => 'work date',
      'notes' => 'notes',
      'status' => 'status',
    ];
  }
}
