<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCategoryObjectiveDetailRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'objective_id' => [
        'required',
        'integer',
        Rule::exists('gh_evaluation_objective', 'id')->whereNull('deleted_at'),
        Rule::unique('gh_evaluation_category_objective')->where(function ($query) {
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
