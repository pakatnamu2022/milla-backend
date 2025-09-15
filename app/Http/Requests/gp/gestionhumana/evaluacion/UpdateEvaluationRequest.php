<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation', 'name')->whereNull('deleted_at')->ignore($this->route('evaluation')),
      ],
      'start_date' => [
        'required',
        'date',
        Rule::unique('gh_evaluation', 'start_date')->whereNull('deleted_at')->ignore($this->route('evaluation'))
      ],
      'end_date' => [
        'required',
        'date',
        'after:start_date',
        Rule::unique('gh_evaluation', 'end_date')->whereNull('deleted_at')->ignore($this->route('evaluation'))
      ],
      'objectivesPercentage' => 'nullable|numeric|min:0|max:100',
      'competencesPercentage' => 'nullable|numeric|min:0|max:100',
      'cycle_id' => [
        'required',
        'exists:gh_evaluation_cycle,id',
        Rule::unique('gh_evaluation', 'cycle_id')->whereNull('deleted_at')->ignore($this->route('evaluation')),
      ],
      'competence_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
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


  public function attributes()
  {
    return [
      'name' => 'nombre',
      'start_date' => 'fecha de inicio',
      'end_date' => 'fecha de fin',
      'objectivesPercentage' => 'porcentaje de objetivos',
      'competencesPercentage' => 'porcentaje de competencias',
      'cycle_id' => 'ciclo',
      'competence_parameter_id' => 'parámetro de competencia',
      'final_parameter_id' => 'parámetro final',
    ];
  }
}
