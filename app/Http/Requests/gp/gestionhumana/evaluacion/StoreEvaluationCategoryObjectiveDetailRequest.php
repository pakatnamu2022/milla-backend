<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationCategoryObjectiveDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'objective_id' => 'required|integer|exists:gh_evaluation_objective,id',
      'category_id' => 'required|integer|exists:gh_hierarchical_category,id',
    ];
  }
}
