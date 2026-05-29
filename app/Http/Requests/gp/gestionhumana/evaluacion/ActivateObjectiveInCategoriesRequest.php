<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class ActivateObjectiveInCategoriesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'category_ids' => 'nullable|array',
      'category_ids.*' => 'integer|exists:gh_hierarchical_category,id',
    ];
  }
}
