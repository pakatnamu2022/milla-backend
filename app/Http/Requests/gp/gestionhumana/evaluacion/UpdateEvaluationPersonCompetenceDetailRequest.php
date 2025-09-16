<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationPersonCompetenceDetailRequest extends StoreRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      // Para update individual
      'result' => [
        'sometimes',
        'numeric',
        'min:0',
        'max:' . Evaluation::find(EvaluationPersonCompetenceDetail::find($this->route('personCompetenceDetail'))->evaluation_id)->max_score_competence
      ],
//    'sometimes|numeric|min:0|max:5',
      'person' => 'sometimes|string|max:255',
      'competence' => 'sometimes|string',
      'sub_competence' => 'sometimes|string',

      // Para update masivo
      'evaluation_id' => 'sometimes|integer|exists:gh_evaluation,id',
      'person_id' => 'sometimes|integer|exists:rrhh_persona,id',
      'competences' => 'sometimes|array',
      'competences.*.id' => 'required|integer|exists:gh_evaluation_person_competence_detail,id',
      'competences.*.result' => 'required|numeric|min:0|max:5',
    ];
  }

  public function attributes(): array
  {
    return [
      'result' => 'resultado',
      'person' => 'persona',
      'competence' => 'competencia',
      'sub_competence' => 'sub competencia',
      'evaluation_id' => 'evaluación',
      'person_id' => 'persona',
      'competences' => 'competencias',
      'competences.*.id' => 'ID de la competencia',
      'competences.*.result' => 'resultado de la competencia',
    ];
  }
}
