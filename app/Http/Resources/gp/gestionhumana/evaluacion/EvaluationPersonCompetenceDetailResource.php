<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonCompetenceDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'evaluation_id' => $this->evaluation_id,
      'evaluator_id' => $this->evaluator_id,
      'person_id' => $this->person_id,
      'competence_id' => $this->competence_id,
      'sub_competence_id' => $this->sub_competence_id,
      'person' => $this->person,
      'evaluator' => $this->evaluator,
      'competence' => $this->competence,
      'sub_competence' => $this->sub_competence,
      'evaluatorType' => $this->evaluatorType,
      'result' => $this->result,
    ];
  }
}
