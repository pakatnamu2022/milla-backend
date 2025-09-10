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
      'id' => $this->id,
      'person' => $this->person->nombre_completo,
      'personCycleDetail' => new EvaluationPersonCycleDetailResource($this->personCycleDetail),
      'evaluation' => $this->evaluation->name,
      'result' => $this->result,
      'compliance' => $this->compliance,
      'qualification' => $this->qualification,
      'comment' => $this->comment,
    ];
  }
}
