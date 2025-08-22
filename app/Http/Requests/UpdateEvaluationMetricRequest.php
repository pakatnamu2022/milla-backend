<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEvaluationMetricRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation_metric', 'name')
          ->whereNull('deleted_at')
          ->ignore($this->route('metric')),
      ],
      'description' => 'nullable|string|max:1000',
    ];
  }
}
