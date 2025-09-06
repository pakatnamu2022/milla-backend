<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'start_date' => 'required|date',
      'end_date' => 'required|date|after:start_date',
      'typeEvaluation' => [
        'required',
        'integer',
        Rule::in(array_keys(config('evaluation.typesEvaluation')))
      ],
      'objectivesPercentage' => 'required|numeric|min:0|max:100',
      'competencesPercentage' => 'required|numeric|min:0|max:100',
      'cycle_id' => 'required|exists:gh_evaluation_cycle,id',
      'competence_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
      'objective_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
      'final_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $data = $this->all();
      if (isset($data['objectivesPercentage']) && isset($data['competencesPercentage'])) {
        $total = $data['objectivesPercentage'] + $data['competencesPercentage'];
        if ($total != 100) {
          $validator->errors()->add('objectivesPercentage', 'La suma de los porcentajes de objetivos y competencias debe ser igual a 100.');
          $validator->errors()->add('competencesPercentage', 'La suma de los porcentajes de objetivos y competencias debe ser igual a 100.');
        }
      }
    });
  }

  public function messages()
  {
    return [
      'typeEvaluation.in' => 'El campo tipo de evaluación debe ser uno de los siguientes: ' . implode(', ', array_keys(config('evaluation.typesEvaluation'))) . ' para los valores '
        . implode(', ', array_values(config('evaluation.typesEvaluation'))) . ' respectivamente.',
    ];
  }

  public function attributes()
  {
    return [
      'name' => 'nombre',
      'start_date' => 'fecha de inicio',
      'end_date' => 'fecha de fin',
      'typeEvaluation' => 'tipo de evaluación',
      'objectivesPercentage' => 'porcentaje de objetivos',
      'competencesPercentage' => 'porcentaje de competencias',
      'cycle_id' => 'ciclo',
      'competence_parameter_id' => 'parámetro de competencia',
      'objective_parameter_id' => 'parámetro de objetivo',
      'final_parameter_id' => 'parámetro final',
    ];
  }
}
