<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class StoreEvaluationPersonDetailRequest extends StoreRequest
{
  function rules(): array
  {
    return [
      'person_id' => 'required|integer|exists:rrhh_persona,id',
    ];
  }
}
