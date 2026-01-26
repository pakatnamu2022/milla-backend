<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Illuminate\Validation\Rule;

class UpdatePayrollPeriodRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'payment_date' => ['nullable', 'date'],
      'status' => ['required', Rule::in(PayrollPeriod::STATUSES)],
    ];
  }

  public function attributes(): array
  {
    return [
      'payment_date' => 'payment date',
      'status' => 'status',
    ];
  }
}
