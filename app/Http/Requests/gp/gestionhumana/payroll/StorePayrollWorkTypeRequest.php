<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollWorkTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => ['required', 'string', 'max:10', 'unique:gh_payroll_work_types,code'],
      'name' => ['required', 'string', 'max:100'],
      'description' => ['nullable', 'string', 'max:255'],
      'multiplier' => ['nullable', 'numeric', 'min:0', 'max:10'],
      'base_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
      'is_extra_hours' => ['nullable', 'boolean'],
      'is_night_shift' => ['nullable', 'boolean'],
      'is_holiday' => ['nullable', 'boolean'],
      'is_sunday' => ['nullable', 'boolean'],
      'active' => ['nullable', 'boolean'],
      'order' => ['nullable', 'integer', 'min:0'],
    ];
  }

  public function attributes(): array
  {
    return [
      'code' => 'code',
      'name' => 'name',
      'description' => 'description',
      'multiplier' => 'multiplier',
      'base_hours' => 'base hours',
      'is_extra_hours' => 'is extra hours',
      'is_night_shift' => 'is night shift',
      'is_holiday' => 'is holiday',
      'is_sunday' => 'is Sunday',
      'active' => 'active',
      'order' => 'order',
    ];
  }
}
