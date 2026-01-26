<?php

namespace App\Http\Requests\gp\gestionhumana\payroll;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\payroll\PayrollConcept;
use Illuminate\Validation\Rule;

class UpdatePayrollConceptRequest extends StoreRequest
{
  public function rules(): array
  {
    $id = $this->route('id');

    return [
      'code' => ['sometimes', 'string', 'max:20', Rule::unique('gh_payroll_concepts', 'code')->ignore($id)],
      'name' => ['sometimes', 'string', 'max:150'],
      'description' => ['nullable', 'string', 'max:500'],
      'type' => ['sometimes', 'string', 'in:' . implode(',', PayrollConcept::TYPES)],
      'category' => ['sometimes', 'string', 'in:' . implode(',', PayrollConcept::CATEGORIES)],
      'formula' => ['nullable', 'string', 'max:1000'],
      'formula_description' => ['nullable', 'string'],
      'is_taxable' => ['nullable', 'boolean'],
      'calculation_order' => ['nullable', 'integer', 'min:0'],
      'active' => ['nullable', 'boolean'],
    ];
  }
}
