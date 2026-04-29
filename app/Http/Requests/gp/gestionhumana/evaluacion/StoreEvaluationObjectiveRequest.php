<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationObjectiveRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string',
      'description' => 'nullable|string|max_digits:1000',
      'metric_id' => 'required|exists:gh_evaluation_metric,id',
      'goalReference' => 'nullable|numeric',
      'fixedWeight' => 'nullable|numeric|min:0',
      'isAscending' => 'nullable|boolean',
    ];
  }
}
