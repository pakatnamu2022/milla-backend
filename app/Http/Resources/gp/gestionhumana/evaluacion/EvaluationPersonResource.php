<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'person' => new WorkerResource($this->person),
      'personCycleDetail' => new EvaluationPersonCycleDetailResource($this->personCycleDetail),
      'evaluation' => new EvaluationResource($this->evaluation),
      'result' => $this->result,
      'compliance' => $this->compliance,
      'qualification' => $this->qualification,
      'comment' => $this->comment,
    ];
  }
}
