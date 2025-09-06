<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'start_date' => $this->start_date,
      'end_date' => $this->end_date,
      'typeEvaluation' => $this->typeEvaluation,
      'typeEvaluationName' => config('evaluation.typesEvaluation')[$this->typeEvaluation] ?? null,
      'objectivesPercentage' => $this->objectivesPercentage,
      'competencesPercentage' => $this->competencesPercentage,
      'cycle_id' => $this->cycle_id,
      'competence_parameter_id' => $this->competence_parameter_id,
      'objective_parameter_id' => $this->objective_parameter_id,
      'final_parameter_id' => $this->final_parameter_id,

      'cycle' => new EvaluationCycleResource($this->cycle),
      'competenceParameter' => new EvaluationParameterResource($this->competenceParameter),
      'objectiveParameter' => new EvaluationParameterResource($this->objectiveParameter),
      'finalParameter' => new EvaluationParameterResource($this->finalParameter),
    ];
  }
}
