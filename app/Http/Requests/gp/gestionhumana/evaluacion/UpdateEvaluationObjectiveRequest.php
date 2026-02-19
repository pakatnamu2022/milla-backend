<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateEvaluationObjectiveRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'nullable|string|max:255',
      'description' => 'nullable|string|max:1000',
      'metric_id' => 'nullable|exists:gh_evaluation_metric,id',
      'goalReference' => 'nullable|numeric|max:255',
      'fixedWeight' => 'nullable|numeric|min:0',
      'isAscending' => 'nullable|boolean',
    ];
  }
}
