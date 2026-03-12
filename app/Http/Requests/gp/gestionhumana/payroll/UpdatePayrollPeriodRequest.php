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
      'year' => ['sometimes', 'integer', 'min:2020', 'max:2100'],
      'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
      'payment_date' => ['nullable', 'date'],
      'biweekly_date' => ['nullable', 'date'],
    ];
  }

  public function attributes(): array
  {
    return [
      'year' => 'year',
      'month' => 'month',
      'payment_date' => 'payment date',
      'biweekly_date' => 'fecha de quincena',
    ];
  }
}
