<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\IndexRequest;

class GetByEvaluationEvaluationPersonCompetenceDetailRequest extends IndexRequest
{
  public function rules(): array
  {
    return array_merge(parent::rules(), [
      'person_id'     => 'nullable|integer',
      'competence_id' => 'nullable|integer',
      'evaluatorType' => 'nullable|integer',
      'person'        => 'nullable|string',
      'competence'    => 'nullable|string',
      'search'        => 'nullable|string',
      'grouped'       => 'nullable|boolean',
    ]);
  }
}
