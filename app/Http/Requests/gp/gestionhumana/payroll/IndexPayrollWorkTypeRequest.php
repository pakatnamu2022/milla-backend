<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class IndexPayrollWorkTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'search' => ['nullable', 'string', 'max:100'],
      'code' => ['nullable', 'string', 'max:10'],
      'active' => ['nullable', 'boolean'],
      'is_extra_hours' => ['nullable', 'boolean'],
      'is_night_shift' => ['nullable', 'boolean'],
      'is_holiday' => ['nullable', 'boolean'],
      'is_sunday' => ['nullable', 'boolean'],
      'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
      'page' => ['nullable', 'integer', 'min:1'],
      'sort' => ['nullable', 'string'],
      'direction' => ['nullable', 'in:asc,desc'],
      'all' => ['nullable', 'string'],
    ];
  }
}
