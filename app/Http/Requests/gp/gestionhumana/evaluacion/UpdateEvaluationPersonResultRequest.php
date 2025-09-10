<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class UpdateEvaluationPersonResultRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'result' => 'required|numeric|min:0',
    ];
  }
}
