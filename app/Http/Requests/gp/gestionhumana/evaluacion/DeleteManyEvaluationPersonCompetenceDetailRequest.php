<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class DeleteManyEvaluationPersonCompetenceDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'ids'   => 'required|array|min:1',
      'ids.*' => 'required|integer|exists:gh_evaluation_person_competence_detail,id',
    ];
  }
}
