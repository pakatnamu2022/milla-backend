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
        'nullable',
        'string',
        'max:255',
        Rule::unique('gh_evaluation', 'name')->whereNull('deleted_at')->ignore($this->route('evaluation')),
      ],
      'start_date' => [
        'nullable',
        'date',
        Rule::unique('gh_evaluation', 'start_date')->whereNull('deleted_at')->ignore($this->route('evaluation'))
      ],
      'end_date' => [
        'nullable',
        'date',
        'after:start_date',
        Rule::unique('gh_evaluation', 'end_date')->whereNull('deleted_at')->ignore($this->route('evaluation'))
      ],
      'objectivesPercentage' => 'nullable|numeric|min:0|max:100',
      'competencesPercentage' => 'nullable|numeric|min:0|max:100',
      'cycle_id' => [
        'nullable',
        'exists:gh_evaluation_cycle,id',
        Rule::unique('gh_evaluation', 'cycle_id')->whereNull('deleted_at')->ignore($this->route('evaluation')),
      ],
      'competence_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
      'final_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
      'status' => [
        'nullable',
        'integer',
        Rule::in(array_keys(config('evaluation.statusEvaluation'))),
      ],
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
      if (isset($data['status']) && $data['status'] == 1) { // Si el estado es "Cerrada"
        $evaluation = $this->route('evaluation');
        if ($evaluation) {
          $evaluationModel = \App\Models\gp\gestionhumana\evaluacion\Evaluation::find($evaluation);
          if ($evaluationModel && $evaluationModel->participants()->count() > 0) {
            $validator->errors()->add('status', 'No se puede reabrir la evaluación porque ya tiene participantes asociados.');
          }
        }
      }
      if (isset($data['status']) && $data['status'] == 2) { // Si el estado es "Cerrada"
        $evaluation = $this->route('evaluation');
        if ($evaluation) {
          $evaluationModel = \App\Models\gp\gestionhumana\evaluacion\Evaluation::find($evaluation);
          if ($evaluationModel && $evaluationModel->participants()->count() == 0) {
            $validator->errors()->add('status', 'No se puede cerrar la evaluación porque no tiene participantes asociados.');
          }
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
