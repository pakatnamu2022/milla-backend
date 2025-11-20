<?php

namespace App\Http\Requests;

class StoreEvaluationParEvaluatorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => 'required|exists:rrhh_persona,id',
      'mate_id' => 'required|exists:rrhh_persona,id',
    ];
  }
}
