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
      'work_type_id' => ['required', 'integer', 'exists:gh_payroll_work_types,id'],
      'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
      'work_date' => ['required', 'date'],
      'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
      'extra_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
      'notes' => ['nullable', 'string', 'max:255'],
      'status' => ['nullable', 'string', 'in:' . implode(',', PayrollSchedule::STATUSES)],
    ];
  }

  public function attributes(): array
  {
    return [
      'worker_id' => 'worker',
      'work_type_id' => 'work type',
      'period_id' => 'period',
      'work_date' => 'work date',
      'hours_worked' => 'hours worked',
      'extra_hours' => 'extra hours',
      'notes' => 'notes',
      'status' => 'status',
    ];
  }
}
