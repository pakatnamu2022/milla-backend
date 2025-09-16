<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;

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
      'result' => 'sometimes|numeric|min:0|max:5',
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

  public function messages(): array
  {
    return [
      'result.numeric' => 'El resultado debe ser un número',
      'result.min' => 'El resultado debe ser mayor o igual a 0',
      'result.max' => 'El resultado debe ser menor o igual a 5',
      'evaluation_id.exists' => 'La evaluación no existe',
      'person_id.exists' => 'La persona no existe',
      'competences.array' => 'Las competencias deben ser un array',
      'competences.*.id.required' => 'El ID de la competencia es requerido',
      'competences.*.id.exists' => 'El detalle de competencia no existe',
      'competences.*.result.required' => 'El resultado es requerido',
      'competences.*.result.numeric' => 'El resultado debe ser un número',
      'competences.*.result.min' => 'El resultado debe ser mayor o igual a 0',
      'competences.*.result.max' => 'El resultado debe ser menor o igual a 5',
    ];
  }
}
