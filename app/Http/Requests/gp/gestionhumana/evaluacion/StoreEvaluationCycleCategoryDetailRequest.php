<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationCycleCategoryDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'categories' => 'required|array|min:1',
      'categories.*' => 'required|integer|exists_soft:gh_hierarchical_category,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'categories' => 'categorías',
      'categories.*' => 'categoría',
    ];
  }
}
