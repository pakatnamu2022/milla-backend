<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;

class StorePayrollWorkTypeSegmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'work_type_id' => ['required', 'integer', 'exists:gh_payroll_work_types,id'],
      'segment_type' => ['required', 'string', 'in:WORK,BREAK'],
      'segment_order' => ['required', 'integer', 'min:1'],
      'duration_hours' => ['required', 'numeric', 'min:0', 'max:24'],
      'multiplier' => ['nullable', 'numeric', 'min:0', 'max:10'],
      'description' => ['nullable', 'string', 'max:255'],
    ];
  }

  public function attributes(): array
  {
    return [
      'work_type_id' => 'work type',
      'segment_type' => 'segment type',
      'segment_order' => 'segment order',
      'duration_hours' => 'duration hours',
      'multiplier' => 'multiplier',
      'description' => 'description',
    ];
  }
}
