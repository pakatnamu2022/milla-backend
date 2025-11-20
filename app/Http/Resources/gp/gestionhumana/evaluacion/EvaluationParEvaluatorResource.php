<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationParEvaluatorResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'worker_id' => $this->worker_id,
      'mate_id' => $this->mate_id,

      // Relationships
      'worker' => WorkerResource::make($this->worker),
      'mate' => WorkerResource::make($this->mate),
    ];
  }
}
