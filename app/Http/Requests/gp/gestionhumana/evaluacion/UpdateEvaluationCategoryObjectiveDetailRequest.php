<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateEvaluationCategoryObjectiveDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'goal' => 'nullable|numeric',
      'weight' => 'nullable|numeric|min:0',
      'active' => 'nullable|boolean',
    ];
  }
}
