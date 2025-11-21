<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationModelRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'categories' => 'required|array|min:1',
      'categories.*' => [
        'required',
        'integer',
        Rule::exists('gh_hierarchical_category', 'id')->whereNull('deleted_at'),
      ],
      'leadership_weight' => 'required|numeric|min:0|max:100',
      'self_weight' => 'required|numeric|min:0|max:100',
      'par_weight' => 'required|numeric|min:0|max:100',
      'report_weight' => 'required|numeric|min:0|max:100',
    ];
  }
}
