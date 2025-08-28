<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class DeleteEvaluationCategoryObjectiveDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'objective_id' => 'required|integer|exists:gh_evaluation_category_objective,objective_id',
      'category_id' => 'required|integer|exists:gh_evaluation_category_objective,category_id',
    ];
  }
}
