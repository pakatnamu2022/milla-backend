<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_date_objectives' => $this->start_date_objectives,
            'end_date_objectives' => $this->end_date_objectives,
            'period_id' => $this->period_id,
            'parameter_id' => $this->parameter_id,
            'status' => (now()->between($this->start_date, $this->end_date) ? 'en proceso' : 'pendiente'),
            'period' => new EvaluationPeriodResource($this->period),
            'parameter' => new EvaluationParameterResource($this->parameter),
            'categories' => EvaluationCycleCategoryDetailResource::collection($this->categories)
        ];
    }
}
