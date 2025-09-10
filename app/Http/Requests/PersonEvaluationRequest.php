<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonEvaluationRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'person_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id',
    ];
  }
}
