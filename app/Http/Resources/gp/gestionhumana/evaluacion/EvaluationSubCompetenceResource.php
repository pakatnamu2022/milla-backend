<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationSubCompetenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'competencia_id' => $this->competencia_id,
            'nombre' => $this->nombre,
            'definicion' => $this->definicion,
            'level1' => $this->level1,
            'level2' => $this->level2,
            'level3' => $this->level3,
            'level4' => $this->level4,
            'level5' => $this->level5,
        ];
    }
}
