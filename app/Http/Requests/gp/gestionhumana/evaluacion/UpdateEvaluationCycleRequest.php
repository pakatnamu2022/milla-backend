<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationCycleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation_cycle')->whereNull('deleted_at')->ignore($this->route('cycle'))
      ],
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
      'start_date_objectives' => 'required|date|date_format:Y-m-d',
      'end_date_objectives' => 'required|date|date_format:Y-m-d|after_or_equal:start_date_objectives',
      'period_id' => 'required|exists:gh_evaluation_periods,id',
      'parameter_id' => 'required|exists:gh_evaluation_parameter,id',
      'typeEvaluation' => [
        'required',
        Rule::in(array_keys(config('evaluation.typesEvaluation')))
      ],
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $endCycle = $this->input('end_date');
      $startObj = $this->input('start_date_objectives');
      $endObj = $this->input('end_date_objectives');

      if ($endCycle && ($startObj || $endObj)) {
        $endCycleDate = strtotime($endCycle);
        $startObjDate = $startObj ? strtotime($startObj) : null;
        $endObjDate = $endObj ? strtotime($endObj) : null;

        if (
          ($startObjDate && $endCycleDate < $startObjDate) ||
          ($endObjDate && $endCycleDate < $endObjDate)
        ) {
          $validator->errors()->add(
            'end_date',
            'La fecha fin del ciclo debe ser mayor o igual a la fecha de inicio y fin de definición de objetivos.'
          );
        }
      }
    });
  }
}
