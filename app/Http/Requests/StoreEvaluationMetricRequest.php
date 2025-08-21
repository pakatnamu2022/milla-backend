<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreEvaluationMetricRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation_metric', 'name')->whereNull('deleted_at'),
      ],
      'description' => [
        'nullable',
        'string',
        'max:1000',
      ],
    ];
  }
}
