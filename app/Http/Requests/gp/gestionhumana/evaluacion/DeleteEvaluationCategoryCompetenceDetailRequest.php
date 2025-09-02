<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;

class DeleteEvaluationCategoryCompetenceDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'competence_id' => 'required|integer|exists:gh_evaluation_category_competence,competence_id',
      'category_id' => 'required|integer|exists:gh_evaluation_category_competence,category_id',
    ];
  }
}
