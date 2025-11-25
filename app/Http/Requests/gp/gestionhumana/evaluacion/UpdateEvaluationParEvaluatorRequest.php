<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationParEvaluatorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'mate_ids' => 'required|array|min:1',
      'mate_ids.*' => [
        'required',
        'integer',
        Rule::exists('rrhh_persona', 'id')->whereNull('deleted_at'),
      ],
    ];
  }
}
