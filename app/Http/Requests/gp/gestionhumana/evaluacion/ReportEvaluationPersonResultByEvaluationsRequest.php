<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class ReportEvaluationPersonResultByEvaluationsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'evaluaciones_id' => 'required|array|min:1',
      'evaluaciones_id.*' => 'required|integer|distinct|exists:gh_evaluation,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'evaluaciones_id' => 'evaluaciones',
      'evaluaciones_id.*' => 'evaluacion',
    ];
  }
}

