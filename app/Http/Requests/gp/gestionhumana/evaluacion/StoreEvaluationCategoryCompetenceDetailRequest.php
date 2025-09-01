<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCategoryCompetenceDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'competence_id' => [
        'required',
        'integer',
        Rule::exists('gh_config_competencias', 'id')->where('status_delete', 0),
        Rule::unique('gh_evaluation_category_competence')->where(function ($query) {
          return $query->where('category_id', $this->category_id)
            ->whereNull('deleted_at');
        })
      ],
      'category_id' => [
        'required',
        'integer',
        Rule::exists('gh_hierarchical_category', 'id')->whereNull('deleted_at')
      ],
    ];
  }
}
