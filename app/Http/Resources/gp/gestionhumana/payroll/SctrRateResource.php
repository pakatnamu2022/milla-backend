<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SctrRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->company->name ?? null,
            'health_rate' => $this->health_rate,
            'pension_rate' => $this->pension_rate,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'is_current' => $this->effective_to === null,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
