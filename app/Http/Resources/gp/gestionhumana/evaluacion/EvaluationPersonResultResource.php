<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonResultResource extends JsonResource
{
  protected $showExtra = false;

  public function showExtra($show = true)
  {
    $this->showExtra = $show;
    return $this;
  }

  public function toArray(Request $request): array
  {
    $response = [
      'id' => $this->id,
      'person_id' => $this->person_id,
      'evaluation_id' => $this->evaluation_id,
      'person' => new WorkerResource($this->person),
      'competencesPercentage' => $this->competencesPercentage,
      'objectivesPercentage' => $this->objectivesPercentage,
      'objectivesResult' => $this->objectivesResult,
      'competencesResult' => $this->competencesResult,
      'result' => $this->result,
    ];

    if ($this->showExtra) {
      $response['details'] = EvaluationPersonResource::collection($this->details);
    }

    return $response;
  }
}
