<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LifeInsurancePolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->company->name ?? null,
            'insurer' => $this->insurer,
            'policy_number' => $this->policy_number,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'days' => $this->days,
            'monthly_rate' => $this->monthly_rate,
            'igv_rate' => $this->igv_rate,
            'exclusion' => $this->exclusion,
            'total_insured_salary' => $this->total_insured_salary,
            'net_premium' => $this->net_premium,
            'workers_count' => $this->workers_count ?? null,
            'workers' => $this->whenLoaded('workers', fn () => $this->workers->map(fn ($w) => [
                'id' => $w->id,
                'worker_id' => $w->worker_id,
                'nombre_completo' => $w->worker->nombre_completo ?? null,
                'vat' => $w->worker->vat ?? null,
                'insured_salary' => $w->insured_salary,
                'net_cost' => round((float)$w->net_cost, 2),
                'total_with_igv' => round((float)$w->total_with_igv, 2),
                'monthly_amount' => round((float)$w->monthly_amount, 2),
            ])),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
