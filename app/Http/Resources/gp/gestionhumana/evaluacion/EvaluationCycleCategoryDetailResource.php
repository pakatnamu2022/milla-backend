<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCycleCategoryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cycle_id' => $this->cycle_id,
            'hierarchical_category_id' => $this->hierarchical_category_id,
            'cycle' => $this->cycle?->name,
            'hierarchical_category' => $this->hierarchicalCategory?->name
        ];
    }
}
