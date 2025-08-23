<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonDetailResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'person' => new WorkerResource($this->person)
    ];
  }
}
