<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollPeriodRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'year' => ['required', 'integer', 'min:2020', 'max:2100'],
      'month' => ['required', 'integer', 'min:1', 'max:12'],
      'payment_date' => ['nullable', 'date'],
      'company_id' => ['nullable', 'integer', 'exists:companies,id'],
    ];
  }

  public function attributes(): array
  {
    return [
      'year' => 'year',
      'month' => 'month',
      'payment_date' => 'payment date',
      'company_id' => 'company',
    ];
  }
}
