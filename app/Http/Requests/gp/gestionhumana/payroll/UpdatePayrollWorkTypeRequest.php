<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollWorkTypeRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'string',
        'max:10',
        Rule::unique('gh_payroll_work_types', 'code')
          ->ignore($this->route('work_type'))
          ->whereNull('deleted_at'),
      ],
      'name' => ['sometimes', 'string', 'max:100'],
      'description' => ['nullable', 'string', 'max:255'],
      'shift_type' => ['nullable', 'string', 'max:50'],
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
}
