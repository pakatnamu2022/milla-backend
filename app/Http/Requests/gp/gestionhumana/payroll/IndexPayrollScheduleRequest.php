<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class IndexPayrollScheduleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'search' => ['nullable', 'string', 'max:100'],
      'worker_id' => ['nullable', 'integer', 'exists:rrhh_persona,id'],
      'work_type_id' => ['nullable', 'integer', 'exists:gh_payroll_work_types,id'],
      'period_id' => ['nullable', 'integer', 'exists:gh_payroll_periods,id'],
      'work_date' => ['nullable', 'array'],
      'work_date.0' => ['nullable', 'date'],
      'work_date.1' => ['nullable', 'date'],
      'status' => ['nullable', 'string'],
      'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
      'page' => ['nullable', 'integer', 'min:1'],
      'sort' => ['nullable', 'string'],
      'direction' => ['nullable', 'in:asc,desc'],
      'all' => ['nullable', 'string'],
    ];
  }
}
