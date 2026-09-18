<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryIncreaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'worker' => [
                'id' => $this->worker->id ?? null,
                'nombre_completo' => $this->worker->nombre_completo ?? null,
                'vat' => $this->worker->vat ?? null,
            ],
            'previous_salary' => $this->previous_salary,
            'new_salary' => $this->new_salary,
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'reason' => $this->reason,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
