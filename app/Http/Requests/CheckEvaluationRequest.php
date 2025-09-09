<?php

namespace App\Http\Requests;

class CheckEvaluationRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ];
  }
}
