<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;

class StoreBulkPayrollScheduleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'period_id' => ['required', 'integer', 'exists:gh_payroll_periods,id'],
      'schedules' => ['required', 'array', 'min:1'],
      'schedules.*.worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'schedules.*.code' => ['nullable', 'string', 'max:50'],
      'schedules.*.work_date' => ['required', 'date'],
      'schedules.*.hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
      'schedules.*.extra_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
      'schedules.*.notes' => ['nullable', 'string', 'max:255'],
      'schedules.*.status' => ['nullable', 'string', 'in:' . implode(',', PayrollSchedule::STATUSES)],
    ];
  }

  public function attributes(): array
  {
    return [
      'period_id' => 'period',
      'schedules' => 'schedules',
      'schedules.*.worker_id' => 'worker',
      'schedules.*.code' => 'code',
      'schedules.*.work_date' => 'work date',
      'schedules.*.hours_worked' => 'hours worked',
      'schedules.*.extra_hours' => 'extra hours',
      'schedules.*.notes' => 'notes',
      'schedules.*.status' => 'status',
    ];
  }
}
