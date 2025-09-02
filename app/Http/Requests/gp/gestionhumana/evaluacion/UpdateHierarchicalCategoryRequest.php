<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateHierarchicalCategoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'nullable|string|max:255',
      'description' => 'nullable|string|max:1000',
      'hasObjectives' => 'nullable|boolean',
      'excluded_from_evaluation' => 'nullable|boolean',
    ];
  }
}
