<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjectiveAdvisorsPeriodPvResource extends JsonResource
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
      'worker_name' => $this->worker?->nombre_completo,
      'amount' => $this->amount,
    ];
  }
}
