<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation', 'name')->whereNull('deleted_at'),
      ],
      'start_date' => [
        'required',
        'date',
        Rule::unique('gh_evaluation', 'start_date')->whereNull('deleted_at'),
      ],
      'end_date' => [
        'required',
        'date',
        'after:start_date',
        Rule::unique('gh_evaluation', 'end_date')->whereNull('deleted_at'),
      ],
      'objectivesPercentage' => 'required|numeric|min:0|max:100',
      'competencesPercentage' => 'required|numeric|min:0|max:100',
      'cycle_id' => [
        'required',
        'exists:gh_evaluation_cycle,id',
        Rule::unique('gh_evaluation', 'cycle_id')->whereNull('deleted_at'),
      ],
      'competence_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
      'final_parameter_id' => 'nullable|exists:gh_evaluation_parameter,id',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $data = $this->all();

      // Validación de suma de porcentajes
      if (isset($data['objectivesPercentage']) && isset($data['competencesPercentage'])) {
        $total = $data['objectivesPercentage'] + $data['competencesPercentage'];
        if ($total != 100) {
          $validator->errors()->add(
            'objectivesPercentage',
            'La suma de los porcentajes de objetivos y competencias debe ser igual a 100.'
          );
          $validator->errors()->add(
            'competencesPercentage',
            'La suma de los porcentajes de objetivos y competencias debe ser igual a 100.'
          );
        }
      }

      // Validación de cruce de fechas
      if (!empty($data['start_date']) && !empty($data['end_date'])) {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        $evaluation = Evaluation::where(function ($query) use ($startDate, $endDate) {
          $query->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function ($query) use ($startDate, $endDate) {
              $query->where('start_date', '<=', $startDate)
                ->where('end_date', '>=', $endDate);
            });
        })->whereNull('deleted_at')->first();

        if ($evaluation) {
          $validator->errors()->add(
            'start_date',
            'La evaluación ' . $evaluation->name . '(' . $evaluation->start_date . ' - ' . $evaluation->end_date . ')' . ' se cruza con el rango de fechas proporcionado.'
          );
          $validator->errors()->add(
            'end_date',
            'La evaluación que cruza con el rango de fechas proporcionado.'
          );
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
