<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class IndexAttendanceRuleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'search' => ['nullable', 'string', 'max:100'],
      'code' => ['nullable', 'string', 'max:10'],
      'hour_type' => ['nullable', 'string', 'max:50'],
      'pay' => ['nullable', 'boolean'],
      'use_shift' => ['nullable', 'boolean'],
      'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
      'page' => ['nullable', 'integer', 'min:1'],
      'sort' => ['nullable', 'string'],
      'direction' => ['nullable', 'in:asc,desc'],
      'all' => ['nullable', 'string'],
    ];
  }
}