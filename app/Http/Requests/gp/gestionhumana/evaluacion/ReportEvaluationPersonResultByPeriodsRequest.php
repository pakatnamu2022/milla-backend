<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class ReportEvaluationPersonResultByPeriodsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'periodos_id' => 'required|array|min:1',
      'periodos_id.*' => 'required|integer|distinct|exists:gh_evaluation_periods,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'periodos_id' => 'periodos',
      'periodos_id.*' => 'periodo',
    ];
  }
}

