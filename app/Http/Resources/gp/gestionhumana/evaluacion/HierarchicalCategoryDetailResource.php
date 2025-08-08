<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionsistema\PositionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HierarchicalCategoryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position?->name,
            'area' => $this->position?->area?->name .
                ($this->position?->area?->sede ? ' - ' . $this->position?->area?->sede->suc_abrev : ''),
            'leadership' => $this->position?->lidership->name,
            'hierarchical_category_id' => $this->hierarchical_category_id,
            'position_id' => $this->position_id,
        ];
    }
}
