<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationPersonCompetenceDetailRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id',
      'person_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluator_id' => 'required|integer|exists:rrhh_persona,id',
      'competence_id' => 'required|integer|exists:gh_config_competencias,id',
      'sub_competence_id' => 'required|integer|exists:gh_config_subcompetencias,id',
      'person' => 'required|string|max:255',
      'competence' => 'required|string',
      'sub_competence' => 'required|string',
      'evaluatorType' => 'required|integer|in:0,1,2,3',
      'result' => 'sometimes|numeric|min:0|max:5', // Asumiendo escala de 1-5
    ];
  }

  public function messages(): array
  {
    return [
      'evaluation_id.required' => 'La evaluación es requerida',
      'evaluation_id.exists' => 'La evaluación no existe',
      'person_id.required' => 'La persona es requerida',
      'person_id.exists' => 'La persona no existe',
      'evaluator_id.required' => 'El evaluador es requerido',
      'evaluator_id.exists' => 'El evaluador no existe',
      'competence_id.required' => 'La competencia es requerida',
      'competence_id.exists' => 'La competencia no existe',
      'sub_competence_id.required' => 'La subcompetencia es requerida',
      'sub_competence_id.exists' => 'La subcompetencia no existe',
      'evaluatorType.required' => 'El tipo de evaluador es requerido',
      'evaluatorType.in' => 'El tipo de evaluador debe ser: 0 (Jefe), 1 (Autoevaluación), 2 (Compañeros), 3 (Reportes)',
      'result.numeric' => 'El resultado debe ser un número',
      'result.min' => 'El resultado debe ser mayor o igual a 0',
      'result.max' => 'El resultado debe ser menor o igual a 5',
    ];
  }
}
