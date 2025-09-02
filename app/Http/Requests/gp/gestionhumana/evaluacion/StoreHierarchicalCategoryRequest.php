<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreHierarchicalCategoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_hierarchical_category', 'name')->whereNull('deleted_at'),
      ],
      'description' => 'nullable|string|max:1000',
      'hasObjectives' => 'required|boolean',
      'excluded_from_evaluation' => 'boolean',
    ];
  }
}
