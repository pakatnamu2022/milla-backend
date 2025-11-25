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
        Rule::exists('rrhh_persona', 'id')->where('status_id', 22),
      ],
      'mate_ids' => 'required|array|min:1',
      'mate_ids.*' => [
        'required',
        'integer',
        Rule::exists('rrhh_persona', 'id')->where('status_id', 22),
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
