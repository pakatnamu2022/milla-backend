<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationPersonRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id',
      'result' => 'nullable|string',
      'compliance' => 'nullable|numeric',
      'qualification' => 'nullable|numeric',
      'comment' => 'nullable|string',
    ];
  }
}
