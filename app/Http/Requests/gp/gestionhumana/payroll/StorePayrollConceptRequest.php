<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollConcept;

class StorePayrollConceptRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => ['required', 'string', 'max:20', 'unique:gh_payroll_concepts,code'],
      'name' => ['required', 'string', 'max:150'],
      'description' => ['nullable', 'string', 'max:500'],
      'type' => ['required', 'string', 'in:' . implode(',', PayrollConcept::TYPES)],
      'category' => ['required', 'string', 'in:' . implode(',', PayrollConcept::CATEGORIES)],
      'formula' => ['nullable', 'string', 'max:1000'],
      'formula_description' => ['nullable', 'string'],
      'is_taxable' => ['nullable', 'boolean'],
      'calculation_order' => ['nullable', 'integer', 'min:0'],
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
      'category' => 'category',
      'formula' => 'formula',
      'formula_description' => 'formula description',
      'is_taxable' => 'is taxable',
      'calculation_order' => 'calculation order',
      'active' => 'active',
    ];
  }
}
