<?php

namespace App\Http\Requests;

class UpdateEvaluationParEvaluatorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => 'required|required|exists:rrhh_persona,id',
      'mate_id' => 'required|required|exists:rrhh_persona,id',
    ];
  }
}
