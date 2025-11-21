<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationParEvaluatorRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => [
        'required',
        'integer',
        Rule::exists('gh_person', 'id')->whereNull('deleted_at'),
      ],
      'mate_ids' => 'required|array|min:1',
      'mate_ids.*' => [
        'required',
        'integer',
        Rule::exists('gh_person', 'id')->whereNull('deleted_at'),
        'different:worker_id', // Un trabajador no puede ser su propio par evaluador
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'mate_ids.*.different' => 'Un trabajador no puede ser su propio par evaluador',
    ];
  }
}
