<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;
use Illuminate\Validation\Rule;

class UpdatePayrollFormulaVariableRequest extends StoreRequest
{
  public function rules(): array
  {
    $id = $this->route('id');

    return [
      'code' => ['sometimes', 'string', 'max:50', Rule::unique('gh_payroll_formula_variables', 'code')->ignore($id)],
      'name' => ['sometimes', 'string', 'max:100'],
      'description' => ['nullable', 'string', 'max:255'],
      'type' => ['sometimes', 'string', 'in:' . implode(',', PayrollFormulaVariable::TYPES)],
      'value' => ['nullable', 'numeric'],
      'source_field' => ['nullable', 'string', 'max:100'],
      'formula' => ['nullable', 'string', 'max:500'],
      'active' => ['nullable', 'boolean'],
    ];
  }
}
