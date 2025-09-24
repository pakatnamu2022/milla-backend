<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCycleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation_cycle')->whereNull('deleted_at')
      ],
      'start_date' => 'required|date|date_format:Y-m-d',
      'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
      'cut_off_date' => 'required|date|date_format:Y-m-d',
//      'start_date_objectives' => 'required|date|date_format:Y-m-d',
//      'end_date_objectives' => 'required|date|date_format:Y-m-d|after_or_equal:start_date_objectives',
      'period_id' => 'required|exists:gh_evaluation_periods,id',
      'parameter_id' => 'required|exists:gh_evaluation_parameter,id',
      'typeEvaluation' => [
        'required',
        'integer',
        Rule::in(array_keys(config('evaluation.typesEvaluation')))
      ],
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $start = $this->input('start_date');
      $end = $this->input('end_date');
      $cutOff = $this->input('cut_off_date');

      if ($start && $end && $cutOff) {
        if ($cutOff < $start || $cutOff > $end) {
          $validator->errors()->add('cut_off_date', 'La fecha de corte debe estar entre la fecha de inicio y la fecha de fin.');
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
      'cut_off_date' => 'fecha de corte',
//      'start_date_objectives' => 'fecha de inicio de definición de objetivos',
//      'end_date_objectives' => 'fecha de fin de definición de objetivos',
      'period_id' => 'periodo',
      'parameter_id' => 'parámetro',
      'typeEvaluation' => 'tipo de evaluación',
    ];
  }

}
