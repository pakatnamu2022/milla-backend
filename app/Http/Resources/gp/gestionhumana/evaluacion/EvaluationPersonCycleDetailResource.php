<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonCycleDetailResource extends JsonResource
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
            'person' => $this->person,
            'chief' => $this->chief,
            'position' => $this->position,
            'sede' => $this->sede,
            'area' => $this->area,
            'category' => $this->category,
            'objective' => $this->objective,
            'goal' => $this->goal,
            'weight' => $this->weight,
            'status' => $this->status,
        ];
    }
}
