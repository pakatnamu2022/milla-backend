<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationParameterDetailResource extends JsonResource
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
      'label' => $this->label,
      'from' => $this->from ? round($this->from, 2) : $this->from,
      'to' => $this->to ? round($this->to, 2) : $this->to,
      'parameter_id' => $this->parameter_id,
    ];
  }
}
