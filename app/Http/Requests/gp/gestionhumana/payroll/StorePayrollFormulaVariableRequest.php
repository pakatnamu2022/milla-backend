<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;

class StorePayrollFormulaVariableRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => ['required', 'string', 'max:50', 'unique:gh_payroll_formula_variables,code'],
      'name' => ['required', 'string', 'max:100'],
      'description' => ['nullable', 'string', 'max:255'],
      'type' => ['required', 'string', 'in:' . implode(',', PayrollFormulaVariable::TYPES)],
      'value' => ['nullable', 'numeric'],
      'source_field' => ['nullable', 'string', 'max:100'],
      'formula' => ['nullable', 'string', 'max:500'],
      'active' => ['nullable', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'code' => 'code',
      'name' => 'name',
      'description' => 'description',
      'type' => 'type',
      'value' => 'value',
      'source_field' => 'source field',
      'formula' => 'formula',
      'active' => 'active',
    ];
  }
}
